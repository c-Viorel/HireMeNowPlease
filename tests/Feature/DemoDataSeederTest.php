<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Job;
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
