<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class)->orderBy('sort_order');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CandidateEducation::class)->orderBy('sort_order');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(CandidateCertification::class)->orderBy('sort_order');
    }

    public function links(): HasMany
    {
        return $this->hasMany(CandidateLink::class)->orderBy('sort_order');
    }

    public function jobPreference(): HasOne
    {
        return $this->hasOne(CandidateJobPreference::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $this->loadMissing(['experiences', 'educations', 'certifications', 'links', 'jobPreference']);

        return [
            'headline' => $this->headline,
            'summary' => $this->summary,
            'phone' => $this->phone,
            'location' => $this->location,
            'skills' => $this->skills ?? [],
            'experiences' => $this->experiences->map(fn (CandidateExperience $experience) => [
                'title' => $experience->title,
                'company' => $experience->company,
                'employment_type' => $experience->employment_type,
                'location' => $experience->location,
                'workplace_type' => $experience->workplace_type,
                'start_date' => $experience->start_date?->toDateString(),
                'end_date' => $experience->end_date?->toDateString(),
                'is_current' => $experience->is_current,
                'description' => $experience->description,
                'skills' => $experience->skills ?? [],
            ])->values()->all(),
            'educations' => $this->educations->map(fn (CandidateEducation $education) => [
                'institution' => $education->institution,
                'degree' => $education->degree,
                'field_of_study' => $education->field_of_study,
                'start_date' => $education->start_date?->toDateString(),
                'end_date' => $education->end_date?->toDateString(),
                'is_current' => $education->is_current,
                'description' => $education->description,
            ])->values()->all(),
            'certifications' => $this->certifications->map(fn (CandidateCertification $certification) => [
                'name' => $certification->name,
                'issuer' => $certification->issuer,
                'issued_at' => $certification->issued_at?->toDateString(),
                'expires_at' => $certification->expires_at?->toDateString(),
                'credential_url' => $certification->credential_url,
            ])->values()->all(),
            'links' => $this->links->map(fn (CandidateLink $link) => [
                'label' => $link->label,
                'url' => $link->url,
            ])->values()->all(),
            'job_preference' => $this->jobPreference ? [
                'availability' => $this->jobPreference->availability,
                'experience_level' => $this->jobPreference->experience_level,
                'desired_salary_min' => $this->jobPreference->desired_salary_min,
                'desired_salary_max' => $this->jobPreference->desired_salary_max,
                'preferred_workplace_types' => $this->jobPreference->preferred_workplace_types ?? [],
                'preferred_employment_types' => $this->jobPreference->preferred_employment_types ?? [],
            ] : null,
        ];
    }
}
