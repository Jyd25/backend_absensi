<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailReportJob;
use App\Mail\AttendanceReportMail;
use App\Models\Attendance;
use App\Models\EmailReport;
use App\Models\User;
use App\Services\AttendanceReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ExportController extends Controller
{
    use ApiResponse;

    protected AttendanceReportService $reportService;

    public function __construct(AttendanceReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function attendance(Request $request): JsonResponse
    {
        $user = $request->user();
        if (in_array($user->role?->name, ['Guru', 'Karyawan'])) {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'required|in:pdf,excel',
        ]);

        $data = $this->reportService->build(
            $request->start_date,
            $request->end_date,
            $request->department_id ? (int) $request->department_id : null
        );

        return $this->successResponse($data);
    }

    /**
     * Queue the attendance report to every active user via email.
     */
    public function emailAttendance(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'required|in:pdf,excel',
        ]);

        $report = $this->reportService->build(
            $request->start_date,
            $request->end_date,
            $request->department_id ? (int) $request->department_id : null
        );

        if (empty($report['items'])) {
            return $this->errorResponse('Tidak ada data kehadiran pada periode ini — email tidak dikirim.', 422);
        }

        $recipients = User::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            return $this->errorResponse('Tidak ada user aktif untuk menerima email.', 422);
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(
                new AttendanceReportMail(
                    $report,
                    $request->format,
                    $recipient->name
                )
            );
        }

        return $this->successResponse([
            'queued_count' => $recipients->count(),
            'period' => $report['period'],
            'format' => $request->format,
        ], "Email laporan sedang dikirim ke {$recipients->count()} user.");
    }

    /**
     * Per-user email status: list every user with employee data along with
     * whether an email report has been queued/sent/failed for the period.
     */
    public function emailStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $start = $request->start_date;
        $end = $request->end_date;

        $users = User::with('employee')
            ->active()
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'email']);

        if ($request->department_id) {
            $users = $users->filter(fn ($u) => $u->employee !== null && (int) $u->employee->department_id === (int) $request->department_id);
        }

        $userIds = $users->pluck('id')->toArray();

        $counts = Attendance::select('employee_id', DB::raw('count(*) as total'))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween(DB::raw('DATE(check_in_time)'), [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('check_in_time')
                            ->whereBetween(DB::raw('DATE(check_out_time)'), [$start, $end]);
                    });
            })
            ->whereIn('employee_id', $users->pluck('employee_id')->toArray())
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $reportsByUser = EmailReport::whereIn('user_id', $userIds)
            ->where('start_date', $start)
            ->where('end_date', $end)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->first());

        $items = $users->values()->map(function ($u) use ($counts, $reportsByUser) {
            $recordsCount = $u->employee_id ? (int) ($counts[$u->employee_id] ?? 0) : 0;
            $report = $reportsByUser->get($u->id);

            return [
                'user_id' => $u->id,
                'employee_id' => $u->employee_id,
                'name' => $u->name,
                'email' => $u->email,
                'nik' => $u->employee?->nik ?? '-',
                'records_count' => $recordsCount,
                'has_attendance' => $recordsCount > 0,
                'report_id' => $report?->id,
                'report_created_at' => $report?->created_at?->toIso8601String(),
                'format' => $report?->format,
                'status' => $report?->status ?? 'not_sent',
                'sent_at' => $report?->sent_at?->toIso8601String(),
                'error_message' => $report?->error_message,
            ];
        })->toArray();

        return $this->successResponse([
            'period' => $start . ' s/d ' . $end,
            'items' => $items,
        ]);
    }

    /**
     * Send a per-user attendance report (their own data) to every active user
     * that has attendance records for the period, and record the attempt.
     */
    public function sendEmails(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'required|in:pdf,excel',
        ]);

        $start = $request->start_date;
        $end = $request->end_date;

        $users = User::with('employee')
            ->active()
            ->whereNotNull('employee_id')
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'email'])
            ->filter(fn ($u) => $u->employee !== null);

        if ($request->department_id) {
            $users = $users->filter(fn ($u) => (int) $u->employee->department_id === (int) $request->department_id);
        }

        $employeeIds = $users->pluck('employee_id')->unique()->toArray();

        $counts = Attendance::select('employee_id', DB::raw('count(*) as total'))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween(DB::raw('DATE(check_in_time)'), [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('check_in_time')
                            ->whereBetween(DB::raw('DATE(check_out_time)'), [$start, $end]);
                    });
            })
            ->whereIn('employee_id', $employeeIds)
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $recipients = $users->filter(fn ($u) => ((int) ($counts[$u->employee_id] ?? 0)) > 0);

        if ($recipients->isEmpty()) {
            return $this->errorResponse('Tidak ada user dengan data kehadiran pada periode ini — email tidak dikirim.', 422);
        }

        foreach ($recipients as $recipient) {
            $emailReport = EmailReport::create([
                'user_id' => $recipient->id,
                'employee_id' => $recipient->employee_id,
                'recipient_email' => $recipient->email,
                'start_date' => $start,
                'end_date' => $end,
                'format' => $request->format,
                'status' => 'pending',
            ]);

            SendEmailReportJob::dispatch($emailReport);
        }

        return $this->successResponse([
            'queued_count' => $recipients->count(),
            'period' => $start . ' s/d ' . $end,
            'format' => $request->format,
        ], "Email laporan per user sedang dikirim ke {$recipients->count()} user.");
    }

    /**
     * Re-send an email report to a single user by creating a new attempt.
     */
    public function resendEmail(Request $request, EmailReport $emailReport): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $newReport = EmailReport::create([
            'user_id' => $emailReport->user_id,
            'employee_id' => $emailReport->employee_id,
            'recipient_email' => $emailReport->recipient_email,
            'start_date' => $emailReport->start_date,
            'end_date' => $emailReport->end_date,
            'format' => $emailReport->format,
            'status' => 'pending',
        ]);

        SendEmailReportJob::dispatch($newReport);

        return $this->successResponse([
            'report_id' => $newReport->id,
        ], 'Email laporan sedang dikirim ulang.');
    }
}
