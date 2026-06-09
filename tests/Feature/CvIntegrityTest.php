<?php

use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Support\Cv\CvIntegrityAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function integrityProfile(User $candidate): CandidateProfile
{
    return CandidateProfile::factory()->for($candidate, 'user')->create([
        'skills' => ['Laravel', 'Kubernetes'],
    ]);
}

it('flags overlapping work experiences', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = integrityProfile($candidate);

    $profile->experiences()->create([
        'title' => 'Backend Engineer',
        'company' => 'Alpha',
        'start_date' => '2020-01-01',
        'end_date' => '2022-06-01',
        'is_current' => false,
        'sort_order' => 0,
    ]);
    $profile->experiences()->create([
        'title' => 'Lead Engineer',
        'company' => 'Beta',
        'start_date' => '2021-01-01',
        'end_date' => '2023-01-01',
        'is_current' => false,
        'sort_order' => 1,
    ]);

    $signals = app(CvIntegrityAnalyzer::class)->analyze($profile);

    expect(collect($signals)->pluck('type'))->toContain('overlap');
});

it('flags long career gaps', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = integrityProfile($candidate);

    $profile->experiences()->create([
        'title' => 'Junior Dev',
        'company' => 'Alpha',
        'start_date' => '2018-01-01',
        'end_date' => '2019-01-01',
        'is_current' => false,
        'sort_order' => 0,
    ]);
    $profile->experiences()->create([
        'title' => 'Mid Dev',
        'company' => 'Beta',
        'start_date' => '2021-06-01',
        'end_date' => '2023-01-01',
        'is_current' => false,
        'sort_order' => 1,
    ]);

    $signals = app(CvIntegrityAnalyzer::class)->analyze($profile);

    expect(collect($signals)->pluck('type'))->toContain('gap');
});

it('returns no signals for a clean timeline', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['skills' => []]);

    $profile->experiences()->create([
        'title' => 'Dev',
        'company' => 'Alpha',
        'start_date' => '2019-01-01',
        'end_date' => '2021-01-01',
        'is_current' => false,
        'sort_order' => 0,
    ]);
    $profile->experiences()->create([
        'title' => 'Senior Dev',
        'company' => 'Beta',
        'start_date' => '2021-02-01',
        'end_date' => null,
        'is_current' => true,
        'sort_order' => 1,
    ]);

    $signals = app(CvIntegrityAnalyzer::class)->analyze($profile);

    expect($signals)->toBeEmpty();
});
