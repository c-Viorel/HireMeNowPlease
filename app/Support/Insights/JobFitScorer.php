<?php

namespace App\Support\Insights;

use App\Models\CandidateProfile;
use App\Models\Job;
use Illuminate\Support\Str;

class JobFitScorer
{
    private const SKILL_TAXONOMY = [
        'laravel', 'php', 'mysql', 'postgresql', 'redis', 'rest api', 'api design', 'vue', 'typescript',
        'tailwind css', 'vite', 'playwright', 'api testing', 'ci/cd', 'sql', 'docker', 'kubernetes',
        'terraform', 'monitoring', 'python', 'pytorch', 'mlops', 'airflow', 'bigquery', 'figma',
        'design systems', 'research', 'prototyping', 'discovery', 'roadmap', 'roadmapping', 'analytics',
        'stakeholders', 'sourcing', 'interviewing', 'ats', 'candidate experience', 'seo', 'copywriting',
        'crm', 'onboarding', 'account planning', 'budgeting', 'forecasting', 'excel', 'ifrs',
        'siem', 'incident response', 'vulnerability management', 'linux', 'process improvement',
        'kpi tracking', 'planning', 'vendor management',
    ];

    public function score(CandidateProfile|array|null $profile, Job $job): FitScore
    {
        $snapshot = $profile instanceof CandidateProfile ? $profile->snapshot() : ($profile ?? []);
        $candidateSkills = $this->candidateSkills($snapshot);
        $requiredSkills = $this->requiredSkills($job);
        $matchedSkills = array_values(array_intersect($candidateSkills, $requiredSkills));
        $missingSkills = array_values(array_diff($requiredSkills, $candidateSkills));

        $skillScore = count($requiredSkills) > 0
            ? (int) round((count($matchedSkills) / count($requiredSkills)) * 100)
            : 55;

        $experienceScore = $this->experienceScore($snapshot, $job);
        $preferenceScore = $this->preferenceScore($snapshot, $job);
        $salaryScore = $this->salaryScore($snapshot, $job);
        $score = (int) round(($skillScore * 0.42) + ($experienceScore * 0.28) + ($preferenceScore * 0.2) + ($salaryScore * 0.1));
        $score = max(0, min(100, $score));

        $strengths = $this->strengths($matchedSkills, $snapshot, $job, $preferenceScore, $salaryScore);
        $gaps = $this->gaps($missingSkills, $snapshot, $job, $preferenceScore, $salaryScore);

        return new FitScore(
            score: $score,
            label: $this->label($score),
            breakdown: [
                ['label' => 'Skills', 'score' => $skillScore, 'detail' => count($matchedSkills).' din '.max(1, count($requiredSkills)).' semnale cheie potrivite'],
                ['label' => 'Experienta', 'score' => $experienceScore, 'detail' => 'Nivel, titluri si descrieri comparate cu rolul'],
                ['label' => 'Preferinte', 'score' => $preferenceScore, 'detail' => 'Mod de lucru si tip contract'],
                ['label' => 'Salariu', 'score' => $salaryScore, 'detail' => 'Aliniere intre range si asteptari'],
            ],
            strengths: $strengths,
            gaps: $gaps,
            matchedSkills: $matchedSkills,
            missingSignals: array_slice($missingSkills, 0, 6),
            recommendation: $this->recommendation($score, $gaps),
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, string>
     */
    private function candidateSkills(array $snapshot): array
    {
        $skills = collect($snapshot['skills'] ?? []);

        foreach ($snapshot['experiences'] ?? [] as $experience) {
            $skills = $skills
                ->merge($experience['skills'] ?? [])
                ->merge($this->taxonomyMatches(implode(' ', [
                    $experience['title'] ?? '',
                    $experience['description'] ?? '',
                ])));
        }

        return $skills
            ->map(fn ($skill) => Str::lower(trim((string) $skill)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function requiredSkills(Job $job): array
    {
        $text = Str::lower($job->title.' '.$job->description);
        $matches = $this->taxonomyMatches($text);

        preg_match('/cunostinte solide de ([^.]+)/i', $job->description, $explicit);
        if (($explicit[1] ?? null) !== null) {
            $matches = array_merge($matches, collect(explode(',', $explicit[1]))
                ->map(fn ($skill) => Str::lower(trim($skill)))
                ->filter()
                ->all());
        }

        return collect($matches)->unique()->values()->take(8)->all();
    }

    /**
     * @return array<int, string>
     */
    private function taxonomyMatches(string $text): array
    {
        $text = Str::lower($text);

        return collect(self::SKILL_TAXONOMY)
            ->filter(fn (string $skill) => str_contains($text, $skill))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function experienceScore(array $snapshot, Job $job): int
    {
        $text = Str::lower(implode(' ', [
            $snapshot['headline'] ?? '',
            $snapshot['summary'] ?? '',
            collect($snapshot['experiences'] ?? [])->pluck('title')->implode(' '),
            collect($snapshot['experiences'] ?? [])->pluck('description')->implode(' '),
        ]));
        $jobWords = collect(preg_split('/[^a-z0-9+#.]+/i', Str::lower($job->title)) ?: [])
            ->filter(fn ($word) => strlen($word) >= 3)
            ->unique();

        $titleHits = $jobWords->filter(fn ($word) => str_contains($text, $word))->count();
        $base = min(70, $titleHits * 18);
        $level = Str::lower((string) $job->experience_level);
        $preferenceLevel = Str::lower((string) data_get($snapshot, 'job_preference.experience_level'));

        if ($level !== '' && ($preferenceLevel === $level || str_contains($text, $level))) {
            $base += 25;
        } elseif ($level === 'mid' && str_contains($text, 'senior')) {
            $base += 18;
        }

        return max(20, min(100, $base + (count($snapshot['experiences'] ?? []) > 0 ? 15 : 0)));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function preferenceScore(array $snapshot, Job $job): int
    {
        $workplacePreferences = data_get($snapshot, 'job_preference.preferred_workplace_types', []);
        $employmentPreferences = data_get($snapshot, 'job_preference.preferred_employment_types', []);
        $score = 40;

        if (empty($workplacePreferences) || in_array($job->workplace_type->value, $workplacePreferences, true)) {
            $score += 30;
        }

        if (empty($employmentPreferences) || in_array($job->employment_type->value, $employmentPreferences, true)) {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function salaryScore(array $snapshot, Job $job): int
    {
        $desiredMin = (int) data_get($snapshot, 'job_preference.desired_salary_min', 0);
        $desiredMax = (int) data_get($snapshot, 'job_preference.desired_salary_max', 0);

        if (! $job->salary_min || ! $job->salary_max || (! $desiredMin && ! $desiredMax)) {
            return 65;
        }

        $desiredMin = $desiredMin ?: $desiredMax;
        $desiredMax = $desiredMax ?: $desiredMin;
        $overlap = max(0, min($job->salary_max, $desiredMax) - max($job->salary_min, $desiredMin));

        if ($overlap > 0) {
            return 100;
        }

        if ($desiredMin <= $job->salary_max * 1.12) {
            return 78;
        }

        return 42;
    }

    /**
     * @param  array<int, string>  $matchedSkills
     * @param  array<string, mixed>  $snapshot
     * @return array<int, string>
     */
    private function strengths(array $matchedSkills, array $snapshot, Job $job, int $preferenceScore, int $salaryScore): array
    {
        $strengths = [];

        if ($matchedSkills !== []) {
            $strengths[] = 'Match pe skill-uri: '.implode(', ', array_slice($matchedSkills, 0, 4)).'.';
        }

        if (count($snapshot['experiences'] ?? []) > 0) {
            $strengths[] = 'Profilul include experienta structurata cu roluri si responsabilitati.';
        }

        if ($preferenceScore >= 80) {
            $strengths[] = 'Preferintele de lucru sunt aliniate cu modul '.str_replace('_', ' ', $job->workplace_type->value).'.';
        }

        if ($salaryScore >= 90) {
            $strengths[] = 'Asteptarile salariale par compatibile cu range-ul publicat.';
        }

        return array_slice($strengths, 0, 4);
    }

    /**
     * @param  array<int, string>  $missingSkills
     * @param  array<string, mixed>  $snapshot
     * @return array<int, string>
     */
    private function gaps(array $missingSkills, array $snapshot, Job $job, int $preferenceScore, int $salaryScore): array
    {
        $gaps = [];

        if ($missingSkills !== []) {
            $gaps[] = 'Nu apar in profil: '.implode(', ', array_slice($missingSkills, 0, 4)).'.';
        }

        if (count($snapshot['experiences'] ?? []) === 0) {
            $gaps[] = 'Lipseste experienta structurata pe roluri.';
        }

        if ($preferenceScore < 80) {
            $gaps[] = 'Preferintele candidatului nu sunt complet aliniate cu rolul.';
        }

        if ($salaryScore < 70) {
            $gaps[] = 'Range-ul salarial ar putea necesita clarificare.';
        }

        return array_slice($gaps, 0, 4);
    }

    private function label(int $score): string
    {
        return match (true) {
            $score >= 82 => 'Potrivire puternica',
            $score >= 65 => 'Potrivire buna',
            $score >= 45 => 'Potrivire partiala',
            default => 'Potrivire slaba',
        };
    }

    /**
     * @param  array<int, string>  $gaps
     */
    private function recommendation(int $score, array $gaps): string
    {
        if ($score >= 82) {
            return 'Merita prioritate: profilul are semnale consistente pentru acest rol.';
        }

        if ($score >= 65) {
            return 'Merita o discutie scurta, mai ales pentru clarificarea zonelor lipsa.';
        }

        return $gaps[0] ?? 'Revizuieste cerintele rolului si profilul inainte de aplicare.';
    }
}
