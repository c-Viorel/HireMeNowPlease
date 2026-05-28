<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CandidateProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CandidateProfileFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (CandidateProfile $profile): void {
            $profile->applications()->eachById(fn (Application $application) => $application->delete());

            if ($profile->cv_path) {
                Storage::disk('local')->delete($profile->cv_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'experience' => 'array',
            'skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
