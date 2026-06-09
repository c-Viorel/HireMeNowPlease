<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Support\Assessments\AssessmentGrader;
use App\Support\Insights\JobFitScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function excelAssessment(): Assessment
{
    $assessment = Assessment::create([
        'slug' => 'excel-intermediar',
        'title' => 'Excel intermediar',
        'category' => 'Office',
        'skill_tag' => 'excel',
        'passing_score' => 70,
    ]);

    $assessment->questions()->create([
        'prompt' => 'Ce functie insumeaza un interval?',
        'choices' => ['SUM', 'AVERAGE', 'COUNT'],
        'correct_index' => 0,
        'sort_order' => 0,
    ]);
    $assessment->questions()->create([
        'prompt' => 'Ce functie cauta o valoare pe verticala?',
        'choices' => ['HLOOKUP', 'VLOOKUP', 'INDEX'],
        'correct_index' => 1,
        'sort_order' => 1,
    ]);

    return $assessment;
}

it('grades an assessment and awards a badge above the passing score', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $assessment = excelAssessment();

    $result = app(AssessmentGrader::class)->grade($assessment, $profile, [0 => 0, 1 => 1]);

    expect($result->score)->toBe(100)
        ->and($result->passed)->toBeTrue()
        ->and($result->badge_awarded)->toBeTrue();
});

it('does not award a badge below the passing score', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $assessment = excelAssessment();

    $result = app(AssessmentGrader::class)->grade($assessment, $profile, [0 => 1, 1 => 2]);

    expect($result->score)->toBe(0)
        ->and($result->passed)->toBeFalse()
        ->and($result->badge_awarded)->toBeFalse();
});

it('adds a verified skill from a passed assessment to the fit score', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['skills' => []]);
    $assessment = excelAssessment();

    AssessmentResult::create([
        'assessment_id' => $assessment->id,
        'candidate_profile_id' => $profile->id,
        'score' => 100,
        'passed' => true,
        'badge_awarded' => true,
    ]);

    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create([
        'title' => 'Operator date',
        'description' => 'Cerinte: cunostinte solide de Excel.',
        'status' => JobStatus::Published,
    ]);

    $fit = app(JobFitScorer::class)->score($profile->fresh(), $job)->toArray();

    expect($fit['matched_skills'])->toContain('excel');
});
