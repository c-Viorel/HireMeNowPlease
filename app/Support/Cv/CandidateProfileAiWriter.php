<?php

namespace App\Support\Cv;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateProfileAiWriter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(User $candidate, array $data, ?string $temporaryCvPath, ?string $originalName): CandidateProfile
    {
        $oldCvPath = $candidate->candidateProfile?->cv_path;
        $cvPath = $this->promoteCv($candidate, $temporaryCvPath, $originalName) ?? $oldCvPath;

        $profile = DB::transaction(function () use ($candidate, $data, $cvPath): CandidateProfile {
            $profile = CandidateProfile::updateOrCreate(
                ['user_id' => $candidate->id],
                [
                    'phone' => $this->nullable($data['phone'] ?? null),
                    'location' => $this->nullable($data['location'] ?? null),
                    'headline' => $this->nullable($data['headline'] ?? null),
                    'summary' => $this->nullable($data['summary'] ?? null),
                    'experience' => [],
                    'skills' => $this->stringList($data['skills'] ?? []),
                    'cv_path' => $cvPath,
                ]
            );

            $this->syncExperiences($profile, $data['experiences'] ?? []);
            $this->syncEducations($profile, $data['educations'] ?? []);
            $this->syncCertifications($profile, $data['certifications'] ?? []);
            $this->syncLinks($profile, $data['links'] ?? []);
            $this->syncJobPreference($profile, $data['job_preference'] ?? []);

            return $profile;
        });

        if ($oldCvPath && $oldCvPath !== $profile->cv_path && Storage::disk('local')->exists($oldCvPath)) {
            Storage::disk('local')->delete($oldCvPath);
        }

        return $profile;
    }

    private function promoteCv(User $candidate, ?string $temporaryCvPath, ?string $originalName): ?string
    {
        if (! $temporaryCvPath || ! Storage::disk('local')->exists($temporaryCvPath)) {
            return null;
        }

        $extension = pathinfo((string) $originalName, PATHINFO_EXTENSION) ?: pathinfo($temporaryCvPath, PATHINFO_EXTENSION);
        $filename = Str::slug(pathinfo((string) $originalName, PATHINFO_FILENAME) ?: 'cv').'-'.Str::random(8).'.'.$extension;
        $targetPath = "cvs/{$candidate->id}/{$filename}";

        Storage::disk('local')->move($temporaryCvPath, $targetPath);

        return $targetPath;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncExperiences(CandidateProfile $profile, array $rows): void
    {
        $profile->experiences()->delete();

        foreach ($rows as $index => $row) {
            if (! is_array($row) || blank($row['title'] ?? null) || blank($row['company'] ?? null)) {
                continue;
            }

            $profile->experiences()->create([
                'title' => $this->nullable($row['title'] ?? null),
                'company' => $this->nullable($row['company'] ?? null),
                'employment_type' => $this->enumValue($row['employment_type'] ?? null, ['full_time', 'part_time', 'contract', 'internship', 'freelance']),
                'location' => $this->nullable($row['location'] ?? null),
                'workplace_type' => $this->enumValue($row['workplace_type'] ?? null, ['remote', 'hybrid', 'on_site']),
                'start_date' => $this->dateValue($row['start_date'] ?? null),
                'end_date' => ($row['is_current'] ?? false) ? null : $this->dateValue($row['end_date'] ?? null),
                'is_current' => (bool) ($row['is_current'] ?? false),
                'description' => $this->nullable($row['description'] ?? null),
                'skills' => $this->stringList($row['skills'] ?? []),
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

        foreach ($rows as $index => $row) {
            if (! is_array($row) || blank($row['institution'] ?? null)) {
                continue;
            }

            $profile->educations()->create([
                'institution' => $this->nullable($row['institution'] ?? null),
                'degree' => $this->nullable($row['degree'] ?? null),
                'field_of_study' => $this->nullable($row['field_of_study'] ?? null),
                'start_date' => $this->dateValue($row['start_date'] ?? null),
                'end_date' => ($row['is_current'] ?? false) ? null : $this->dateValue($row['end_date'] ?? null),
                'is_current' => (bool) ($row['is_current'] ?? false),
                'description' => $this->nullable($row['description'] ?? null),
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

        foreach ($rows as $index => $row) {
            if (! is_array($row) || blank($row['name'] ?? null)) {
                continue;
            }

            $profile->certifications()->create([
                'name' => $this->nullable($row['name'] ?? null),
                'issuer' => $this->nullable($row['issuer'] ?? null),
                'issued_at' => $this->dateValue($row['issued_at'] ?? null),
                'expires_at' => $this->dateValue($row['expires_at'] ?? null),
                'credential_url' => filter_var($row['credential_url'] ?? null, FILTER_VALIDATE_URL) ? $row['credential_url'] : null,
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

        foreach ($rows as $index => $row) {
            if (! is_array($row) || blank($row['label'] ?? null) || ! filter_var($row['url'] ?? null, FILTER_VALIDATE_URL)) {
                continue;
            }

            $profile->links()->create([
                'label' => $this->nullable($row['label'] ?? null),
                'url' => $row['url'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncJobPreference(CandidateProfile $profile, array $row): void
    {
        $profile->jobPreference()->updateOrCreate(
            [],
            [
                'availability' => $this->nullable($row['availability'] ?? null),
                'experience_level' => $this->nullable($row['experience_level'] ?? null),
                'desired_salary_min' => $this->positiveInteger($row['desired_salary_min'] ?? null),
                'desired_salary_max' => $this->positiveInteger($row['desired_salary_max'] ?? null),
                'preferred_workplace_types' => collect($row['preferred_workplace_types'] ?? [])->intersect(['remote', 'hybrid', 'on_site'])->values()->all(),
                'preferred_employment_types' => collect($row['preferred_employment_types'] ?? [])->intersect(['full_time', 'part_time', 'contract', 'internship'])->values()->all(),
            ]
        );
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function enumValue(mixed $value, array $allowed): ?string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function positiveInteger(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
