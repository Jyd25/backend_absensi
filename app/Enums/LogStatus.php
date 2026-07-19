<?php

namespace App\Enums;

enum LogStatus: string
{
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Berhasil',
            self::Failed => 'Gagal',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
