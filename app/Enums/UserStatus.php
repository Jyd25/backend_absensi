<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
            self::Locked => 'Dikunci',
            self::Suspended => 'Ditangguhkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            self::Locked => 'danger',
            self::Suspended => 'warning',
        };
    }
}
