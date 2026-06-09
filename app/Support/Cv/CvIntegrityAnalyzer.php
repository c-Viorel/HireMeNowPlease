<?php

namespace App\Support\Cv;

use App\Models\CandidateProfile;
use Illuminate\Support\Carbon;

/**
 * Detects timeline inconsistencies in a candidate profile that a recruiter
 * should verify (overlapping roles, long unexplained gaps).
 */
class CvIntegrityAnalyzer
{
    private const GAP_THRESHOLD_MONTHS = 12;

    /**
     * @return array<int, array{type: string, message: string}>
     */
    public function analyze(CandidateProfile|array $profile): array
    {
        $snapshot = $profile instanceof CandidateProfile ? $profile->snapshot() : $profile;

        $experiences = collect($snapshot['experiences'] ?? [])
            ->filter(fn ($experience) => ! empty($experience['start_date']))
            ->map(fn ($experience) => [
                'title' => $experience['title'] ?? 'Rol',
                'start' => Carbon::parse($experience['start_date']),
                'end' => ! empty($experience['end_date'])
                    ? Carbon::parse($experience['end_date'])
                    : (($experience['is_current'] ?? false) ? Carbon::now() : Carbon::parse($experience['start_date'])),
            ])
            ->sortBy(fn ($experience) => $experience['start']->timestamp)
            ->values();

        $signals = [];
        $signals = array_merge($signals, $this->overlaps($experiences));
        $signals = array_merge($signals, $this->gaps($experiences));

        return $signals;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{title: string, start: Carbon, end: Carbon}>  $experiences
     * @return array<int, array{type: string, message: string}>
     */
    private function overlaps($experiences): array
    {
        $signals = [];

        for ($i = 0; $i < $experiences->count(); $i++) {
            for ($j = $i + 1; $j < $experiences->count(); $j++) {
                $a = $experiences[$i];
                $b = $experiences[$j];

                // Allow up to one month of natural transition overlap.
                if ($b['start']->copy()->addMonth()->lt($a['end'])) {
                    $signals[] = [
                        'type' => 'overlap',
                        'message' => 'Suprapunere de perioade intre "'.$a['title'].'" si "'.$b['title'].'". Verifica datele.',
                    ];
                }
            }
        }

        return $signals;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{title: string, start: Carbon, end: Carbon}>  $experiences
     * @return array<int, array{type: string, message: string}>
     */
    private function gaps($experiences): array
    {
        $signals = [];

        for ($i = 0; $i < $experiences->count() - 1; $i++) {
            $current = $experiences[$i];
            $next = $experiences[$i + 1];
            $gapMonths = $current['end']->diffInMonths($next['start']);

            if ($gapMonths >= self::GAP_THRESHOLD_MONTHS) {
                $signals[] = [
                    'type' => 'gap',
                    'message' => 'Gol in cariera de aproximativ '.(int) round($gapMonths).' luni inainte de "'.$next['title'].'".',
                ];
            }
        }

        return $signals;
    }
}
