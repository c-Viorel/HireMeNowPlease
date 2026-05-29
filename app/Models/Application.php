<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Application extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (Application $application): void {
            if ($application->cv_path) {
                Storage::disk('local')->delete($application->cv_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'profile_snapshot' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }
}
