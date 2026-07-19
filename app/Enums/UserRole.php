<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'Administrator';
    case Pimpinan = 'Pimpinan';
    case Guru = 'Guru';
    case Karyawan = 'Karyawan';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Pimpinan => 'Pimpinan',
            self::Guru => 'Guru',
            self::Karyawan => 'Karyawan',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
