<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function diasporaCompany(): Company
{
    $owner = User::factory()->create(['role' => UserRole::Employer]);

    return Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
}

it('lists only relocation or remote jobs on the diaspora hub', function () {
    $company = diasporaCompany();

    $relocationJob = Job::factory()->for($company)->create([
        'title' => 'Inginer cu pachet de relocare',
        'status' => JobStatus::Published,
        'workplace_type' => WorkplaceType::OnSite,
        'offers_relocation' => true,
    ]);

    $remoteJob = Job::factory()->for($company)->create([
        'title' => 'Developer remote din strainatate',
        'status' => JobStatus::Published,
        'workplace_type' => WorkplaceType::Remote,
        'offers_relocation' => false,
    ]);

    $localOnsiteJob = Job::factory()->for($company)->create([
        'title' => 'Casier magazin local',
        'status' => JobStatus::Published,
        'workplace_type' => WorkplaceType::OnSite,
        'offers_relocation' => false,
    ]);

    $this->get(route('diaspora.index'))
        ->assertOk()
        ->assertSee('Inginer cu pachet de relocare')
        ->assertSee('Developer remote din strainatate')
        ->assertDontSee('Casier magazin local');
});

it('stores diaspora preferences on the candidate job preference', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = \App\Models\CandidateProfile::factory()->for($candidate, 'user')->create();

    $preference = $profile->jobPreference()->create([
        'current_country' => 'Germania',
        'open_to_relocation' => true,
        'timezone' => 'Europe/Berlin',
    ]);

    expect($preference->fresh()->open_to_relocation)->toBeTrue()
        ->and($preference->fresh()->current_country)->toBe('Germania');
});
