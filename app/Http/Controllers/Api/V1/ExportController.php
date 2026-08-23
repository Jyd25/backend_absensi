<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\AttendanceReportMail;
use App\Models\User;
use App\Services\AttendanceReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
