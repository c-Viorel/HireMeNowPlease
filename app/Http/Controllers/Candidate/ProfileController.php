<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateProfileRequest;
use App\Models\CandidateProfile;
use App\Support\Copilot\CandidateCoach;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit(CandidateCoach $candidateCoach): View
    {
        $profile = auth()->user()->candidateProfile?->load([
            'experiences',
            'educations',
            'certifications',
            'links',
            'jobPreference',
        ]);

        return view('candidate.profile.edit', [
            'profile' => $profile,
            'profileCoach' => $candidateCoach->profileAdvice($profile),
        ]);
    }

    public function update(CandidateProfileRequest $request): RedirectResponse
    {
        $userId = auth()->id();
        $profile = auth()->user()->candidateProfile;
        $oldCvPath = $profile?->cv_path;
        $validated = $request->validated();

        $profileData = [
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'headline' => $validated['headline'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'experience' => [],
            'skills' => collect(explode(',', $validated['skills'] ?? ''))
                ->map(fn (string $skill) => trim($skill))
                ->filter()
                ->values()
                ->all(),
            'cv_path' => $oldCvPath,
        ];

        if ($request->hasFile('cv')) {
            $profileData['cv_path'] = $request->file('cv')->store("cvs/{$userId}", 'local');
        }

        $profile = DB::transaction(function () use ($userId, $profileData, $validated): CandidateProfile {
            $profile = CandidateProfile::updateOrCreate(['user_id' => $userId], $profileData);

            $this->syncExperiences($profile, $validated['experiences'] ?? []);
            $this->syncEducations($profile, $validated['educations'] ?? []);
            $this->syncCertifications($profile, $validated['certifications'] ?? []);
            $this->syncLinks($profile, $validated['links'] ?? []);
            $this->syncJobPreference($profile, $validated);

            return $profile;
        });

        if ($oldCvPath && $oldCvPath !== $profile->cv_path && Storage::disk('local')->exists($oldCvPath)) {
            Storage::disk('local')->delete($oldCvPath);
        }

        return redirect()->route('candidate.profile.edit')
            ->with('status', 'candidate-profile-updated');
    }

    public function downloadCv(): StreamedResponse
    {
        $profile = auth()->user()->candidateProfile;

        abort_if(! $profile?->cv_path || ! Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->download($profile->cv_path);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncExperiences(CandidateProfile $profile, array $rows): void
    {
        $profile->experiences()->delete();

        foreach ($this->filledRows($rows, ['title', 'company', 'start_date']) as $index => $row) {
            $isCurrent = (bool) ($row['is_current'] ?? false);

            $profile->experiences()->create([
                'title' => $row['title'],
                'company' => $row['company'],
                'employment_type' => $row['employment_type'] ?? null,
                'location' => $row['location'] ?? null,
                'workplace_type' => $row['workplace_type'] ?? null,
                'start_date' => $row['start_date'],
                'end_date' => $isCurrent ? null : ($row['end_date'] ?? null),
                'is_current' => $isCurrent,
                'description' => $row['description'] ?? null,
                'skills' => $this->commaList($row['skills'] ?? ''),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncEducations(CandidateProfile $profile, array $rows): void
    {
        $profile->educations()->delete();

        foreach ($this->filledRows($rows, ['institution']) as $index => $row) {
            $isCurrent = (bool) ($row['is_current'] ?? false);

            $profile->educations()->create([
                'institution' => $row['institution'],
                'degree' => $row['degree'] ?? null,
                'field_of_study' => $row['field_of_study'] ?? null,
                'start_date' => $row['start_date'] ?? null,
                'end_date' => $isCurrent ? null : ($row['end_date'] ?? null),
                'is_current' => $isCurrent,
                'description' => $row['description'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncCertifications(CandidateProfile $profile, array $rows): void
    {
        $profile->certifications()->delete();

        foreach ($this->filledRows($rows, ['name']) as $index => $row) {
            $profile->certifications()->create([
                'name' => $row['name'],
                'issuer' => $row['issuer'] ?? null,
                'issued_at' => $row['issued_at'] ?? null,
                'expires_at' => $row['expires_at'] ?? null,
                'credential_url' => $row['credential_url'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncLinks(CandidateProfile $profile, array $rows): void
    {
        $profile->links()->delete();

        foreach ($this->filledRows($rows, ['label', 'url']) as $index => $row) {
            $profile->links()->create([
                'label' => $row['label'],
                'url' => $row['url'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncJobPreference(CandidateProfile $profile, array $validated): void
    {
        $profile->jobPreference()->updateOrCreate(
            [],
            [
                'availability' => $validated['availability'] ?? null,
                'experience_level' => $validated['experience_level'] ?? null,
                'desired_salary_min' => $validated['desired_salary_min'] ?? null,
                'desired_salary_max' => $validated['desired_salary_max'] ?? null,
                'preferred_workplace_types' => $validated['preferred_workplace_types'] ?? [],
                'preferred_employment_types' => $validated['preferred_employment_types'] ?? [],
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $requiredKeys
     * @return array<int, array<string, mixed>>
     */
    private function filledRows(array $rows, array $requiredKeys): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && collect($requiredKeys)->every(fn ($key) => filled($row[$key] ?? null)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function commaList(?string $value): array
    {
        return collect(explode(',', $value ?? ''))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
