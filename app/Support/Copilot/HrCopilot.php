<?php

namespace App\Support\Copilot;

use App\Models\Application;
use App\Support\Insights\JobFitScorer;

class HrCopilot
{
    public function __construct(private readonly JobFitScorer $fitScorer)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function brief(Application $application): array
    {
        $application->loadMissing(['job.company', 'candidateProfile', 'candidate']);
        $fit = $this->fitScorer->score($application->profile_snapshot ?: $application->candidateProfile?->snapshot(), $application->job)->toArray();
        $score = (int) $fit['score'];

        return [
            'title' => $score >= 75 ? 'Prioritizeaza candidatul' : ($score >= 55 ? 'Clarifica rapid potrivirea' : 'Verifica zonele lipsa inainte de interviu'),
            'summary' => $this->summary($application, $fit),
            'strengths' => $fit['strengths'],
            'concerns' => $fit['gaps'] ?: ['Nu sunt semnale critice lipsa in profilul curent.'],
            'questions' => $this->questions($fit),
            'next_action' => $this->nextAction($score),
        ];
    }

    /**
     * @param  array<string, mixed>  $fit
     */
    private function summary(Application $application, array $fit): string
    {
        return "{$application->candidate->name} are scor {$fit['score']}% pentru {$application->job->title}. {$fit['recommendation']}";
    }

    /**
     * @param  array<string, mixed>  $fit
     * @return array<int, string>
     */
    private function questions(array $fit): array
    {
        $questions = [];

        foreach (array_slice($fit['missing_signals'] ?? [], 0, 3) as $signal) {
            $questions[] = "Poti descrie o situatie recenta in care ai folosit {$signal}?";
        }

        $questions[] = 'Care a fost cel mai relevant proiect pentru responsabilitatile acestui rol?';
        $questions[] = 'Ce asteptari ai de la echipa, ritm de lucru si feedback in primele 90 de zile?';

        return array_slice($questions, 0, 5);
    }

    private function nextAction(int $score): string
    {
        return match (true) {
            $score >= 82 => 'Trimite invitatie la interviu si pregateste discutia pe proiectele similare.',
            $score >= 65 => 'Programeaza un screening de 20 minute pentru zonele neclare.',
            default => 'Cere completari punctuale inainte de a consuma timp de interviu.',
        };
    }
}
