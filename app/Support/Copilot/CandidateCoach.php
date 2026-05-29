<?php

namespace App\Support\Copilot;

use App\Models\CandidateProfile;
use App\Models\Job;
use App\Support\Insights\JobFitScorer;

class CandidateCoach
{
    public function __construct(private readonly JobFitScorer $fitScorer)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAdvice(?CandidateProfile $profile): array
    {
        if (! $profile) {
            return [
                'score' => 0,
                'title' => 'Profilul tau are nevoie de baza',
                'actions' => [
                    'Adauga headline, locatie si un rezumat profesional.',
                    'Completeaza cel putin un rol cu date, rezultate si skill-uri.',
                    'Seteaza preferintele de lucru si range-ul salarial dorit.',
                ],
            ];
        }

        $profile->loadMissing(['experiences', 'educations', 'certifications', 'links', 'jobPreference']);
        $actions = [];

        if (blank($profile->headline)) {
            $actions[] = 'Scrie un headline specific, nu doar titlul generic.';
        }

        if (blank($profile->summary) || strlen((string) $profile->summary) < 120) {
            $actions[] = 'Adauga un rezumat de 3-5 fraze cu domenii, impact si tipul de rol cautat.';
        }

        if (($profile->skills ?? []) === []) {
            $actions[] = 'Adauga 6-10 skill-uri cautabile, separate clar.';
        }

        if ($profile->experiences->count() === 0) {
            $actions[] = 'Adauga experienta pe roluri cu data de inceput si responsabilitati.';
        } elseif ($profile->experiences->contains(fn ($experience) => blank($experience->description))) {
            $actions[] = 'Completeaza descrierile rolurilor cu rezultate si tehnologii folosite.';
        }

        if (! $profile->jobPreference) {
            $actions[] = 'Seteaza preferinte de job: remote/hybrid/on-site, contract si salariu dorit.';
        }

        if (! $profile->cv_path) {
            $actions[] = 'Incarca un CV curent ca angajatorii sa poata descarca versiunea stabila.';
        }

        $score = max(35, 100 - (count($actions) * 12));

        return [
            'score' => min(100, $score),
            'title' => $score >= 85 ? 'Profil pregatit pentru aplicari' : 'Profil bun, dar poate converti mai bine',
            'actions' => array_slice($actions ?: ['Pastreaza profilul actualizat si personalizeaza mesajul pentru fiecare rol.'], 0, 5),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function jobAdvice(?CandidateProfile $profile, Job $job): ?array
    {
        if (! $profile) {
            return null;
        }

        $fit = $this->fitScorer->score($profile, $job)->toArray();

        return [
            'fit' => $fit,
            'pitch' => $this->pitch($fit, $job),
            'actions' => array_slice(array_merge(
                $fit['gaps'] ?? [],
                ['Personalizeaza mesajul de aplicare cu 1 proiect relevant pentru '.$job->title.'.']
            ), 0, 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $fit
     */
    private function pitch(array $fit, Job $job): string
    {
        $skills = implode(', ', array_slice($fit['matched_skills'] ?? [], 0, 3));

        if ($skills !== '') {
            return "Pentru {$job->title}, deschide mesajul cu experienta ta in {$skills} si explica impactul concret.";
        }

        return "Pentru {$job->title}, explica rapid ce proiect relevant ai facut si de ce rolul se potriveste obiectivelor tale.";
    }
}
