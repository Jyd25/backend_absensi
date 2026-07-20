<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    use ApiResponse;

    public function attendance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'required|in:pdf,excel',
        ]);

        $query = Attendance::with(['employee.department', 'employee.position', 'location'])
            ->whereBetween(DB::raw('DATE(check_in_time)'), [$request->start_date, $request->end_date]);

        if ($request->department_id) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }

        $attendances = $query->orderBy('check_in_time')->get();

        $grouped = $attendances->groupBy(fn ($a) => $a->employee?->name ?? 'Unknown')
            ->map(function ($records, $name) {
                $employee = $records->first()->employee;
                return [
                    'name' => $name,
                    'nik' => $employee?->nik ?? '-',
                    'department' => $employee->department?->name ?? '-',
                    'position' => $employee->position?->name ?? '-',
                    'records' => $records->map(fn ($a) => [
                        'date' => $a->check_in_time ? \Carbon\Carbon::parse($a->check_in_time)->format('d/m/Y') : '-',
                        'check_in' => $a->check_in_time ? \Carbon\Carbon::parse($a->check_in_time)->format('H:i') : '-',
                        'check_out' => $a->check_out_time ? \Carbon\Carbon::parse($a->check_out_time)->format('H:i') : '-',
                        'status' => match($a->attendance_status) {
                            'present' => 'Hadir',
                            'late' => 'Terlambat',
                            'absent' => 'Alpha',
                            'permission' => 'Izin',
                            'sick' => 'Sakit',
                            default => $a->attendance_status ?? '-',
                        },
                        'location' => $a->location?->location_name ?? '-',
                        'face' => $a->face_status === 'matched' ? 'Ya' : 'Tidak',
                    ])->toArray(),
                ];
            });

        return $this->successResponse([
            'title' => 'Laporan Kehadiran',
            'period' => $request->start_date . ' s/d ' . $request->end_date,
            'items' => $grouped->values(),
        ]);
    }
}
