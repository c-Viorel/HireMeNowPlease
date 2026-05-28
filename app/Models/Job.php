<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkplaceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    /** @use HasFactory<\Database\Factories\JobFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (Job $job): void {
            $job->applications()->eachById(fn (Application $application) => $application->delete());
        });
    }

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'workplace_type' => WorkplaceType::class,
            'status' => JobStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', JobStatus::Published)
            ->whereHas('company', function (Builder $query): void {
                $query
                    ->where('status', 'approved')
                    ->whereHas('owner', function (Builder $query): void {
                        $query->where('is_active', true);
                    });
            });
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }
}
