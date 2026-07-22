<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\LocationStatus;
use App\Enums\ProcessStatus;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceProcess;
use App\Models\AttendanceLocation;
use App\Models\WorkSchedule;
use App\Repositories\AttendanceRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceService extends BaseService
{
    protected AttendanceRepository $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
        $this->attendanceRepository = $attendanceRepository;
    }

    public function getTodayByEmployee($employeeId)
    {
        return $this->attendanceRepository->getTodayByEmployee($employeeId);
    }

    public function isPastCheckInDeadline(): bool
    {
        $hour = Carbon::now()->hour;
        return $hour >= 10;
    }

    public function withEmployee()
    {
        return $this->attendanceRepository->withEmployee();
    }

    public function getByDateRange($start, $end)
    {
        return $this->attendanceRepository->getByDateRange($start, $end);
    }

    public function checkIn(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $employee = $user->employee;

            if (!empty($data['location_id'])) {
                $location = AttendanceLocation::findOrFail($data['location_id']);
            } else {
                $location = AttendanceLocation::where('is_active', true)
                    ->orderByRaw("( POW(latitude - ?, 2) + POW(longitude - ?, 2) )", [$data['latitude'], $data['longitude']])
                    ->first();
                if (!$location) {
                    $location = AttendanceLocation::where('is_active', true)->firstOrFail();
                }
            }

            $distance = $this->calculateDistance(
                $data['latitude'], $data['longitude'],
                $location->latitude, $location->longitude
            );

            $locationStatus = $distance <= $location->radius
                ? LocationStatus::InsideRadius
                : LocationStatus::OutsideRadius;

            $schedule = $employee->schedule;
            $attendanceStatus = AttendanceStatus::Present;
            $lateMinutes = 0;

            if ($schedule) {
                $checkInTime = Carbon::now();
                $isSaturday = $checkInTime->isSaturday();

                if ($isSaturday && $schedule->saturday_start_time) {
                    $scheduleStart = Carbon::parse($schedule->saturday_start_time);
                } else {
                    $scheduleStart = Carbon::parse($schedule->start_time);
                }

                $tolerance = $schedule->tolerance_minutes ?? 0;
                $lateThreshold = $scheduleStart->copy()->addMinutes($tolerance);

                if ($checkInTime->gt($lateThreshold)) {
                    $attendanceStatus = AttendanceStatus::Late;
                    $lateMinutes = $scheduleStart->diffInMinutes($checkInTime) - $tolerance;
                }
            }

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'location_id' => $location->id,
                'schedule_id' => $schedule?->id,
                'attendance_type' => AttendanceType::CheckIn,
                'check_in_time' => now(),
                'check_out_time' => null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'distance' => $distance,
                'location_status' => $locationStatus,
                'attendance_status' => $attendanceStatus,
                'face_score' => $data['face_score'] ?? null,
                'face_status' => $data['face_status'] ?? null,
                'photo_data' => $data['photo_data'] ?? null,
                'device' => $data['device'] ?? null,
                'ip_address' => request()->ip(),
                'address' => $data['address'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            AttendanceHistory::create([
                'attendance_id' => $attendance->id,
                'action' => 'check_in',
                'performed_by' => $user->id,
                'description' => "Check-in at {$location->location_name}" .
                    ($lateMinutes > 0 ? " (Late by {$lateMinutes} minutes)" : ''),
            ]);

            $this->createProcessRecords($attendance->id, 'check_in');

            return $attendance->load(['employee', 'location', 'schedule']);
        });
    }

    public function checkOut($attendanceId, $user, array $data = []): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $user, $data) {
            if ($attendanceId) {
                $attendance = Attendance::findOrFail($attendanceId);

                $attendance->update([
                    'check_out_time' => now(),
                    'face_score' => $data['face_score'] ?? $attendance->face_score,
                    'face_status' => $data['face_status'] ?? $attendance->face_status,
                    'photo_data' => $data['photo_data'] ?? $attendance->photo_data,
                ]);

                $workMinutes = 0;
                if ($attendance->check_in_time) {
                    $checkIn = Carbon::parse($attendance->check_in_time);
                    $checkOut = Carbon::now();
                    $workMinutes = $checkIn->diffInMinutes($checkOut);
                }

                AttendanceHistory::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'check_out',
                    'performed_by' => $user->id,
                    'description' => 'Check-out completed. Work duration: ' .
                        sprintf('%dh %dm', floor($workMinutes / 60), $workMinutes % 60),
                ]);

                $this->createProcessRecords($attendance->id, 'check_out');
            } else {
                $employee = $user->employee;

                if (!empty($data['location_id'])) {
                    $location = AttendanceLocation::findOrFail($data['location_id']);
                } else {
                    $location = AttendanceLocation::where('is_active', true)
                        ->orderByRaw("( POW(latitude - ?, 2) + POW(longitude - ?, 2) )", [$data['latitude'] ?? 0, $data['longitude'] ?? 0])
                        ->first();
                    if (!$location) {
                        $location = AttendanceLocation::where('is_active', true)->firstOrFail();
                    }
                }

                $distance = 0;
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $distance = $this->calculateDistance(
                        $data['latitude'], $data['longitude'],
                        $location->latitude, $location->longitude
                    );
                }

                $locationStatus = $distance <= $location->radius
                    ? LocationStatus::InsideRadius
                    : LocationStatus::OutsideRadius;

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'location_id' => $location->id,
                    'schedule_id' => $employee->schedule?->id,
                    'attendance_type' => AttendanceType::CheckOut,
                    'check_in_time' => null,
                    'check_out_time' => now(),
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'distance' => $distance,
                    'location_status' => $locationStatus,
                    'attendance_status' => AttendanceStatus::Late,
                    'face_score' => $data['face_score'] ?? null,
                    'face_status' => $data['face_status'] ?? null,
                    'photo_data' => $data['photo_data'] ?? null,
                    'ip_address' => request()->ip(),
                    'address' => $data['address'] ?? null,
                    'remarks' => 'Presensi terlambat — check-in kosong, menunggu disetujui admin.',
                ]);

                AttendanceHistory::create([
                    'attendance_id' => $attendance->id,
                    'action' => 'check_out',
                    'performed_by' => $user->id,
                    'description' => "Presensi terlambat (check-in kosong) at {$location->location_name}. Menunggu penyesuaian check-in oleh admin.",
                ]);

                $this->createProcessRecords($attendance->id, 'check_out');
            }

            return $attendance->load(['employee', 'location', 'schedule']);
        });
    }

    public function getHistory($employeeId, Request $request)
    {
        $query = Attendance::where('employee_id', $employeeId)
            ->with(['employee', 'location', 'schedule'])
            ->latest('check_in_time');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('check_in_time', [
                $request->start_date,
                $request->end_date . ' 23:59:59',
            ]);
        }

        if ($request->has('status')) {
            $query->where('attendance_status', $request->status);
        }

        return $query->paginate($request->get('per_page', 15));
    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    protected function createProcessRecords($attendanceId, string $action): void
    {
        $steps = [
            ['step' => 'location_verification', 'status' => ProcessStatus::Completed, 'description' => 'Location verified'],
            ['step' => 'schedule_verification', 'status' => ProcessStatus::Completed, 'description' => 'Schedule verified'],
            ['step' => 'record_creation', 'status' => ProcessStatus::Completed, 'description' => ucfirst($action) . ' record created'],
            ['step' => 'history_logging', 'status' => ProcessStatus::Completed, 'description' => 'History logged'],
        ];

        foreach ($steps as $step) {
            AttendanceProcess::create(array_merge($step, [
                'attendance_id' => $attendanceId,
                'processed_at' => now(),
            ]));
        }
    }
}
