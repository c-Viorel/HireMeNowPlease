<?php

namespace App\Support\Insights;

final readonly class FitScore
{
    /**
     * @param  array<int, array{label: string, score: int, detail: string}>  $breakdown
     * @param  array<int, string>  $strengths
     * @param  array<int, string>  $gaps
     * @param  array<int, string>  $matchedSkills
     * @param  array<int, string>  $missingSignals
     */
    public function __construct(
        public int $score,
        public string $label,
        public array $breakdown,
        public array $strengths,
        public array $gaps,
        public array $matchedSkills,
        public array $missingSignals,
        public string $recommendation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'label' => $this->label,
            'breakdown' => $this->breakdown,
            'strengths' => $this->strengths,
            'gaps' => $this->gaps,
            'matched_skills' => $this->matchedSkills,
            'missing_signals' => $this->missingSignals,
            'recommendation' => $this->recommendation,
        ];
    }
}
