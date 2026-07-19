<?php

namespace App\Enums;

enum LocationStatus: string
{
    case InsideRadius = 'inside_radius';
    case OutsideRadius = 'outside_radius';

    public function label(): string
    {
        return match ($this) {
            self::InsideRadius => 'Dalam Radius',
            self::OutsideRadius => 'Luar Radius',
        };
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
