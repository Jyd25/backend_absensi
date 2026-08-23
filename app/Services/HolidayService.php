<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    public static function holidayOn(Carbon $date): ?Holiday
    {
        return Holiday::whereDate('date', $date->toDateString())->first();
    }

    public static function isSunday(Carbon $date): bool
    {
        return (int) $date->format('w') === 0;
    }

    /**
     * Non-working day = Sunday or registered national/collective holiday.
     *
     * @return array{reason: string, name: string}|null
     */
    public static function nonWorkingReason(Carbon $date): ?array
    {
        if (self::isSunday($date)) {
            return ['reason' => 'sunday', 'name' => 'Hari Minggu'];
        }

        $holiday = self::holidayOn($date);

        if ($holiday) {
            return [
                'reason' => 'holiday',
                'name' => $holiday->name,
            ];
        }

        return null;
    }

    /**
     * Auto-fill a "libur" attendance row for the given employee and date.
     * Existing real attendances are never overwritten.
     */
    public static function markLibur(int $employeeId, Carbon $date, string $remarks): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $remarks) {
            $existing = Attendance::where('employee_id', $employeeId)
                ->where(function ($query) use ($date) {
                    $query->whereDate('check_in_time', $date->toDateString())
                        ->orWhereDate('check_out_time', $date->toDateString());
                })
                ->latest('check_in_time')
                ->first();

            if ($existing && $existing->attendance_status !== AttendanceStatus::Libur) {
                return $existing;
            }

            if ($existing) {
                $existing->update(['remarks' => $remarks]);

                return $existing;
            }

            return Attendance::create([
                'employee_id' => $employeeId,
                'attendance_type' => AttendanceType::CheckIn->value,
                'check_in_time' => $date->copy()->startOfDay(),
                'attendance_status' => AttendanceStatus::Libur->value,
                'remarks' => $remarks,
            ]);
        });
    }
}
