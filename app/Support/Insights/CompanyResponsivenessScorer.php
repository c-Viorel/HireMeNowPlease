<?php

namespace App\Support\Insights;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Carbon;

class CompanyResponsivenessScorer
{
    /**
     * @return array<string, mixed>
     */
    public function scoreCompany(Company $company): array
    {
        $applications = Application::query()
            ->with(['conversation.messages'])
            ->whereHas('job', fn ($query) => $query->where('company_id', $company->id))
            ->latest()
            ->take(120)
            ->get();

        return $this->scoreApplications($applications, $company->owner_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function scoreJob(Job $job): array
    {
        $job->loadMissing('company');

        $applications = $job->applications()
            ->with(['conversation.messages'])
            ->latest()
            ->take(80)
            ->get();

        return $this->scoreApplications($applications, $job->company->owner_id);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Application>  $applications
     * @return array<string, mixed>
     */
    private function scoreApplications($applications, int $ownerId): array
    {
        $total = $applications->count();
        $firstResponseHours = [];
        $unanswered = 0;

        foreach ($applications as $application) {
            $firstEmployerMessage = $application->conversation?->messages
                ->where('sender_id', $ownerId)
                ->sortBy('created_at')
                ->first();

            if ($firstEmployerMessage) {
                $firstResponseHours[] = max(1, Carbon::parse($application->created_at)->diffInHours($firstEmployerMessage->created_at));
            } elseif ($application->created_at->lt(now()->subDays(3))) {
                $unanswered++;
            }
        }

        $responded = count($firstResponseHours);
        $responseRate = $total > 0 ? (int) round(($responded / $total) * 100) : 100;
        $avgHours = $responded > 0 ? (int) round(array_sum($firstResponseHours) / $responded) : null;
        $timeliness = match (true) {
            $avgHours === null => $total > 0 ? 35 : 100,
            $avgHours <= 12 => 100,
            $avgHours <= 24 => 90,
            $avgHours <= 48 => 78,
            $avgHours <= 96 => 62,
            default => 42,
        };
        $unansweredPenalty = $total > 0 ? min(30, (int) round(($unanswered / $total) * 100)) : 0;
        $score = max(10, min(100, (int) round(($responseRate * 0.58) + ($timeliness * 0.42) - $unansweredPenalty)));

        return [
            'score' => $score,
            'label' => $this->label($score),
            'response_rate' => $responseRate,
            'average_response_hours' => $avgHours,
            'unanswered_applications' => $unanswered,
            'sample_size' => $total,
            'risk' => $this->risk($score),
            'signals' => $this->signals($responseRate, $avgHours, $unanswered, $total),
        ];
    }

    private function label(int $score): string
    {
        return match (true) {
            $score >= 82 => 'Raspuns foarte bun',
            $score >= 65 => 'Raspuns bun',
            $score >= 45 => 'Raspuns variabil',
            default => 'Risc de ghosting',
        };
    }

    private function risk(int $score): string
    {
        return match (true) {
            $score >= 82 => 'scazut',
            $score >= 65 => 'moderat',
            $score >= 45 => 'ridicat',
            default => 'foarte ridicat',
        };
    }

    /**
     * @return array<int, string>
     */
    private function signals(int $responseRate, ?int $avgHours, int $unanswered, int $total): array
    {
        if ($total === 0) {
            return ['Compania nu are inca istoric de aplicari pe platforma.'];
        }

        $signals = [
            "Rata de raspuns: {$responseRate}% din ultimele {$total} aplicari.",
            $avgHours ? "Timp mediu pana la primul raspuns: {$avgHours} ore." : 'Nu exista inca raspunsuri masurabile.',
        ];

        if ($unanswered > 0) {
            $signals[] = "{$unanswered} aplicari mai vechi de 3 zile nu au primit raspuns.";
        } else {
            $signals[] = 'Nu exista aplicari vechi fara raspuns in esantion.';
        }

        return $signals;
    }
}
