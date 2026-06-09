<?php

namespace App\Support\Assessments;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\CandidateProfile;

class AssessmentGrader
{
    /**
     * Grade submitted answers (questionIndex => chosenChoiceIndex) and persist
     * the result, awarding a verified badge when the candidate passes.
     *
     * @param  array<int, int>  $answers
     */
    public function grade(Assessment $assessment, CandidateProfile $profile, array $answers): AssessmentResult
    {
        $questions = $assessment->questions()->get();
        $total = $questions->count();
        $correct = 0;

        foreach ($questions->values() as $index => $question) {
            if ((int) ($answers[$index] ?? -1) === (int) $question->correct_index) {
                $correct++;
            }
        }

        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
        $passed = $score >= $assessment->passing_score;

        return AssessmentResult::updateOrCreate(
            [
                'assessment_id' => $assessment->id,
                'candidate_profile_id' => $profile->id,
            ],
            [
                'score' => $score,
                'passed' => $passed,
                'badge_awarded' => $passed,
            ]
        );
    }
}
