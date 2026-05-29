<?php

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\User;
use App\Support\Insights\CompanyResponsivenessScorer;
use App\Support\Insights\JobFitScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function differentiatorCompany(?User $owner = null): Company
{
    $owner ??= User::factory()->create(['role' => UserRole::Employer]);

    return Company::factory()
        ->for($owner, 'owner')
        ->create(['status' => 'approved']);
}

function differentiatorCandidateProfile(User $candidate): CandidateProfile
{
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create([
        'headline' => 'Senior Laravel Engineer',
        'summary' => 'Builds APIs, marketplace flows and operational products with Laravel and SQL.',
        'skills' => ['Laravel', 'PHP', 'MySQL', 'Redis', 'REST API'],
    ]);

    $profile->experiences()->create([
        'title' => 'Senior Laravel Engineer',
        'company' => 'Product Labs',
        'employment_type' => 'full_time',
        'location' => 'Remote',
        'workplace_type' => 'remote',
        'start_date' => '2021-01-01',
        'end_date' => null,
        'is_current' => true,
        'description' => 'Owned Laravel APIs, Redis queues and MySQL performance for marketplace products.',
        'skills' => ['Laravel', 'Redis', 'MySQL', 'API design'],
        'sort_order' => 0,
    ]);

    $profile->jobPreference()->create([
        'availability' => '30 days',
        'experience_level' => 'senior',
        'desired_salary_min' => 18000,
        'desired_salary_max' => 26000,
        'preferred_workplace_types' => ['remote', 'hybrid'],
        'preferred_employment_types' => ['full_time'],
    ]);

    return $profile;
}

it('calculates explainable fit scores from profile, job and preferences', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = differentiatorCandidateProfile($candidate);
    $job = Job::factory()->for(differentiatorCompany())->create([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Cerinte: Cunostinte solide de Laravel, MySQL, Redis, REST API.',
        'employment_type' => EmploymentType::FullTime,
        'workplace_type' => WorkplaceType::Remote,
        'experience_level' => 'senior',
        'salary_min' => 18000,
        'salary_max' => 26000,
    ]);

    $fit = app(JobFitScorer::class)->score($profile, $job)->toArray();

    expect($fit['score'])->toBeGreaterThanOrEqual(80)
        ->and($fit['matched_skills'])->toContain('laravel')
        ->and($fit['breakdown'])->toHaveCount(4)
        ->and($fit['recommendation'])->not->toBeEmpty();
});

it('shows anti ghosting and candidate coaching on public job pages', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    differentiatorCandidateProfile($candidate);
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = differentiatorCompany($owner);
    $job = Job::factory()->for($company)->create([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Cerinte: Cunostinte solide de Laravel, MySQL, Redis, REST API.',
        'status' => JobStatus::Published,
        'workplace_type' => WorkplaceType::Remote,
    ]);

    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $candidate->candidateProfile->id,
        'status' => ApplicationStatus::Viewed,
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(3),
    ]);
    $conversation = Conversation::create(['application_id' => $application->id]);
    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $owner->id,
        'body' => 'Thanks for applying.',
        'created_at' => $application->created_at->copy()->addHours(8),
    ]);

    $this->actingAs($candidate)->get(route('jobs.show', [$company, $job]))
        ->assertOk()
        ->assertSee('Anti-ghosting score')
        ->assertSee('Potrivirea ta pentru rol')
        ->assertSee('Career Coach');
});

it('stores fit and responsiveness snapshots when applying', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    differentiatorCandidateProfile($candidate);
    $company = differentiatorCompany();
    $job = Job::factory()->for($company)->create([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Cerinte: Cunostinte solide de Laravel, MySQL, Redis.',
        'status' => JobStatus::Published,
    ]);

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'I match the Laravel requirements.',
    ])->assertRedirect(route('candidate.applications.index'));

    $application = Application::firstOrFail();

    expect($application->fit_snapshot['score'])->toBeGreaterThan(60)
        ->and($application->responsiveness_snapshot['score'])->toBeInt()
        ->and($application->profile_snapshot['headline'])->toBe('Senior Laravel Engineer');
});

it('lets the owning employer save a structured interview scorecard', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = differentiatorCandidateProfile($candidate);
    $company = differentiatorCompany($owner);
    $job = Job::factory()->for($company)->create();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Interview,
    ]);

    $this->actingAs($owner)->post(route('employer.applications.scorecard', $application), [
        'recommendation' => 'yes',
        'notes' => 'Strong ownership and clear examples.',
        'items' => [
            ['criterion' => 'Role fit', 'score' => 5, 'evidence' => 'Relevant work.'],
            ['criterion' => 'Technical / functional depth', 'score' => 4, 'evidence' => 'Good API examples.'],
            ['criterion' => 'Communication', 'score' => 4, 'evidence' => 'Clear answers.'],
        ],
    ])->assertRedirect();

    expect($application->fresh()->scorecard)
        ->recommendation->toBe('yes')
        ->overall_score->toBe(87)
        ->items->toHaveCount(3);
});

it('scores employer responsiveness from application conversations', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = differentiatorCandidateProfile($candidate);
    $company = differentiatorCompany($owner);
    $job = Job::factory()->for($company)->create();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
        'created_at' => now()->subDays(5),
    ]);
    $conversation = Conversation::create(['application_id' => $application->id]);
    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $owner->id,
        'body' => 'Let us talk.',
        'created_at' => $application->created_at->copy()->addHours(6),
    ]);

    $score = app(CompanyResponsivenessScorer::class)->scoreCompany($company);

    expect($score['score'])->toBeGreaterThanOrEqual(80)
        ->and($score['response_rate'])->toBe(100)
        ->and($score['average_response_hours'])->toBe(6);
});
