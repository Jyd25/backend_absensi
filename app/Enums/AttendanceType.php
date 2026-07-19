<?php

namespace App\Enums;

enum AttendanceType: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Absen Masuk',
            self::CheckOut => 'Absen Keluar',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
