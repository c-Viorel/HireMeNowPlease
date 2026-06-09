<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratuit',
            self::Pro => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    /**
     * Maximum number of active (published) jobs allowed. Null means unlimited.
     */
    public function activeJobLimit(): ?int
    {
        return match ($this) {
            self::Free => 3,
            self::Pro => 25,
            self::Enterprise => null,
        };
    }

    public function monthlyPrice(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Pro => 49900,
            self::Enterprise => 199900,
        };
    }
}
