<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    /**
     * Build grouped attendance report data shared by the export endpoint,
     * the PDF generator and the bulk email feature.
     */
    public function build(string $startDate, string $endDate, ?int $departmentId = null): array
    {
        $query = Attendance::with(['employee.department', 'employee.position', 'location'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(check_in_time)'), [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->whereNull('check_in_time')
                            ->whereBetween(DB::raw('DATE(check_out_time)'), [$startDate, $endDate]);
                    });
            });

        if ($departmentId) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        $attendances = $query->orderBy('check_in_time')->get();

        $grouped = $attendances
            ->groupBy(fn ($a) => $a->employee?->name ?? 'Unknown')
            ->map(function ($records, $name) {
                $employee = $records->first()->employee;

                return [
                    'name' => $name,
                    'nik' => $employee?->nik ?? '-',
                    'department' => $employee->department?->name ?? '-',
                    'position' => $employee->position?->name ?? '-',
                    'records' => $records->map(fn ($a) => $this->mapRecord($a))->toArray(),
                ];
            });

        return [
            'title' => 'Laporan Kehadiran',
            'period' => $startDate . ' s/d ' . $endDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'items' => $grouped->values()->toArray(),
        ];
    }

    private function mapRecord($a): array
    {
        $statusValue = $a->attendance_status?->value ?? $a->attendance_status;

        return [
            'date' => $a->check_in_time
                ? Carbon::parse($a->check_in_time)->format('d/m/Y')
                : ($a->check_out_time ? Carbon::parse($a->check_out_time)->format('d/m/Y') : '-'),
            'check_in' => $a->check_in_time ? Carbon::parse($a->check_in_time)->format('H:i') : '-',
            'check_out' => $a->check_out_time ? Carbon::parse($a->check_out_time)->format('H:i') : '-',
            'status' => match ($statusValue) {
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'absent' => 'Alpha',
                'permission' => 'Izin',
                'sick' => 'Sakit',
                'libur' => 'Libur',
                default => $a->attendance_status?->label() ?? $a->attendance_status ?? '-',
            },
            'status_checkout' => $a->status_checkout ?? '-',
            'checkin_address' => $a->address ?? '-',
            'checkout_address' => $a->checkout_address ?? '-',
            'location' => $a->location?->location_name ?? '-',
            'face' => ($a->face_status?->value ?? null) === 'matched' ? 'Ya' : 'Tidak',
            'remarks' => $a->remarks ?? '-',
        ];
    }
}
