<?php

namespace App\Enums;

enum SalaryType: string
{
    case Net = 'net';
    case Gross = 'gross';

    public function label(): string
    {
        return match ($this) {
            self::Net => 'net',
            self::Gross => 'brut',
        };
    }
}
