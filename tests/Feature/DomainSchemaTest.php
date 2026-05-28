<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults new users to active candidate accounts from the factory', function () {
    $user = User::factory()->make();

    expect($user->role)->toBe(UserRole::Candidate)
        ->and($user->is_active)->toBeTrue();
});

it('creates the core candidate employer job application graph', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $candidateProfile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $candidateProfile->id,
        'message' => 'I am interested in this role.',
        'status' => ApplicationStatus::Submitted,
    ]);

    $conversation = Conversation::create(['application_id' => $application->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $employer->id,
        'body' => 'Thanks for applying.',
    ]);

    Shortlist::create([
        'company_id' => $company->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
    ]);

    expect($candidate->candidateProfile->is($candidateProfile))->toBeTrue()
        ->and($employer->companies)->toHaveCount(1)
        ->and($company->jobs)->toHaveCount(1)
        ->and($job->applications)->toHaveCount(1)
        ->and($application->conversation->messages)->toHaveCount(1)
        ->and($company->shortlists)->toHaveCount(1);
});

it('rejects applications using another candidates profile', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $otherCandidate = User::factory()->create(['role' => UserRole::Candidate]);
    $otherCandidateProfile = CandidateProfile::factory()->for($otherCandidate, 'user')->create();
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create();

    expect(fn () => Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $otherCandidateProfile->id,
        'status' => ApplicationStatus::Submitted,
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate company level shortlist entries', function () {
    $company = Company::factory()->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);

    Shortlist::create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
    ]);

    expect(fn () => Shortlist::create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
    ]))->toThrow(QueryException::class);
});
