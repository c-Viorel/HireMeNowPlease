<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\Shortlist;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds public demo accounts and a full demo job catalog', function () {
    $this->seed(DatabaseSeeder::class);

    $candidate = User::where('email', 'candidate@hireme.local')->first();
    $employer = User::where('email', 'hr@hireme.local')->first();

    expect($candidate)->not->toBeNull()
        ->and($candidate->role)->toBe(UserRole::Candidate)
        ->and($candidate->candidateProfile)->not->toBeNull()
        ->and($candidate->candidateProfile->experiences)->not->toBeEmpty()
        ->and($candidate->candidateProfile->educations)->not->toBeEmpty()
        ->and($candidate->candidateProfile->jobPreference)->not->toBeNull()
        ->and(Hash::check('demo1234', $candidate->password))->toBeTrue()
        ->and($employer)->not->toBeNull()
        ->and($employer->role)->toBe(UserRole::Employer)
        ->and(Hash::check('demo1234', $employer->password))->toBeTrue();

    $jobs = Job::query()->publiclyVisible()->get();

    expect($jobs)->toHaveCount(150)
        ->and($jobs->where('workplace_type', WorkplaceType::Remote))->not->toBeEmpty()
        ->and($jobs->where('workplace_type', WorkplaceType::Hybrid))->not->toBeEmpty()
        ->and($jobs->where('workplace_type', WorkplaceType::OnSite))->not->toBeEmpty()
        ->and($jobs->where('status', JobStatus::Published))->toHaveCount(150)
        ->and($jobs->whereNull('salary_min'))->toBeEmpty()
        ->and($jobs->whereNull('salary_max'))->toBeEmpty();

    expect($jobs->pluck('title')->unique())->toHaveCount(150);
    expect($jobs->pluck('company_id')->unique()->count())->toBeGreaterThanOrEqual(10);

    $sample = $jobs->firstWhere('title', 'Senior Laravel Engineer');

    expect($sample)->not->toBeNull()
        ->and($sample->description)->toContain('Responsabilitati')
        ->and($sample->description)->toContain('Cerinte')
        ->and($sample->description)->toContain('Beneficii');
});

it('seeds demo applications, conversations, messages, and shortlist activity idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $candidate = User::where('email', 'candidate@hireme.local')->firstOrFail();
    $employer = User::where('email', 'hr@hireme.local')->firstOrFail();

    $candidateApplications = Application::query()
        ->where('candidate_id', $candidate->id)
        ->with('conversation.messages')
        ->get();

    expect(User::where('role', UserRole::Candidate)->count())->toBeGreaterThanOrEqual(13)
        ->and($candidateApplications)->toHaveCount(10)
        ->and($candidateApplications->pluck('status')->unique()->count())->toBeGreaterThanOrEqual(5)
        ->and($candidateApplications->pluck('status')->all())->toContain(ApplicationStatus::Interview)
        ->and(Application::whereHas('job.company', fn ($query) => $query->where('owner_id', $employer->id))->count())->toBeGreaterThanOrEqual(45)
        ->and(Conversation::count())->toBeGreaterThanOrEqual(24)
        ->and(Message::count())->toBeGreaterThanOrEqual(70)
        ->and(Shortlist::count())->toBeGreaterThanOrEqual(12);

    expect($candidateApplications->filter(fn (Application $application) => $application->conversation?->messages->isNotEmpty()))->toHaveCount(7);
    expect($candidateApplications->whereNotNull('profile_snapshot'))->toHaveCount(10);
    expect($candidateApplications->first()->profile_snapshot['experiences'] ?? [])->not->toBeEmpty();
    expect(Application::query()->selectRaw('job_id, candidate_id, count(*) as duplicate_count')->groupBy('job_id', 'candidate_id')->having('duplicate_count', '>', 1)->count())->toBe(0);
});
