<?php

namespace App\Enums;

enum JobCategory: string
{
    case WhiteCollar = 'white_collar';
    case BlueCollar = 'blue_collar';

    public function label(): string
    {
        return match ($this) {
            self::WhiteCollar => 'Birou / specialist',
            self::BlueCollar => 'Meserii / operational',
        };
    }
}
