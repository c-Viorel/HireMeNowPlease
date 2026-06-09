<?php

namespace App\Support\Insights;

use App\Models\Company;
use App\Models\EmployerReview;

class EmployerTrustScore
{
    public function __construct(private readonly CompanyResponsivenessScorer $responsivenessScorer)
    {
    }

    /**
     * Combine responsiveness (anti-ghosting) with verified candidate ratings
     * into a single public trust score (0-100).
     *
     * @return array<string, mixed>
     */
    public function forCompany(Company $company): array
    {
        $responsiveness = $this->responsivenessScorer->scoreCompany($company);

        $reviews = EmployerReview::query()
            ->where('company_id', $company->id)
            ->where('status', 'published')
            ->where('is_verified', true)
            ->get();

        $reviewCount = $reviews->count();
        $averageRating = $reviewCount > 0 ? round((float) $reviews->avg('rating'), 1) : null;
        $wouldApplyAgain = $reviewCount > 0
            ? (int) round(($reviews->where('would_apply_again', true)->count() / $reviewCount) * 100)
            : null;

        $ratingScore = $averageRating !== null ? (int) round(($averageRating / 5) * 100) : null;

        if ($ratingScore === null) {
            $score = $responsiveness['score'];
        } else {
            // More reviews give the rating component more weight (cap 0.5).
            $ratingWeight = min(0.5, 0.2 + ($reviewCount * 0.05));
            $score = (int) round(($responsiveness['score'] * (1 - $ratingWeight)) + ($ratingScore * $ratingWeight));
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => $this->label($score),
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
            'would_apply_again_rate' => $wouldApplyAgain,
            'responsiveness' => $responsiveness,
        ];
    }

    private function label(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Angajator de incredere',
            $score >= 70 => 'Angajator solid',
            $score >= 50 => 'Rezultate mixte',
            $score >= 30 => 'Necesita atentie',
            default => 'Risc ridicat',
        };
    }
}
