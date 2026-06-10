<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Ciorna',
            self::Pending => 'In asteptare',
            self::Published => 'Publicat',
            self::Closed => 'Inchis',
            self::Rejected => 'Respins',
        };
    }

    /**
     * Tailwind chip classes reflecting the status meaning (green = live,
     * amber = pending, slate = draft, red = rejected, neutral = closed).
     */
    public function chipClass(): string
    {
        return match ($this) {
            self::Published => 'bg-emerald-100 text-emerald-700',
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Draft => 'bg-slate-100 text-slate-600',
            self::Closed => 'bg-slate-200 text-slate-700',
            self::Rejected => 'bg-red-100 text-red-700',
        };
    }
}
