<?php

namespace App\Enums;

enum FaceStatus: string
{
    case Matched = 'matched';
    case Unmatched = 'unmatched';

    public function label(): string
    {
        return match ($this) {
            self::Matched => 'Wajah Cocok',
            self::Unmatched => 'Wajah Tidak Cocok',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
