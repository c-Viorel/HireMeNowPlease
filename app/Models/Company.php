<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (Company $company): void {
            $company->jobs()->eachById(fn (Job $job) => $job->delete());
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EmployerReview::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Aprobata',
            'pending' => 'In asteptare',
            'rejected' => 'Respinsa',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusChipClass(): string
    {
        return match ($this->status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'pending' => 'bg-amber-100 text-amber-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
