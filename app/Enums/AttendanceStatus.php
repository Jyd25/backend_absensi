<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Permission = 'permission';
    case Leave = 'leave';
    case Sick = 'sick';
    case Absent = 'absent';
    case Libur = 'libur';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Permission => 'Izin',
            self::Leave => 'Cuti',
            self::Sick => 'Sakit',
            self::Absent => 'Tidak Hadir',
            self::Libur => 'Libur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late => 'warning',
            self::Permission => 'info',
            self::Leave => 'info',
            self::Sick => 'danger',
            self::Absent => 'danger',
            self::Libur => 'info',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
