<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\OnboardingChecklist;
use App\Models\User;
use App\Support\Onboarding\CimDraftGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function onboardingApplication(User $owner, User $candidate): Application
{
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create([
        'title' => 'Sudor autorizat',
        'status' => JobStatus::Published,
        'salary_min' => 6000,
        'salary_max' => 8000,
    ]);
    $profile = $candidate->candidateProfile ?? CandidateProfile::factory()->for($candidate, 'user')->create();

    return Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Interview,
    ]);
}

it('creates an onboarding checklist with default itm tasks when an application is accepted', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $application = onboardingApplication($owner, $candidate);

    $this->actingAs($owner)
        ->patch(route('employer.applications.status', $application), [
            'status' => ApplicationStatus::Accepted->value,
        ])
        ->assertRedirect();

    $checklist = OnboardingChecklist::where('application_id', $application->id)->first();

    expect($checklist)->not->toBeNull()
        ->and($checklist->tasks()->count())->toBeGreaterThanOrEqual(4);
});

it('generates a romanian cim draft with key terms', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'name' => 'Maria Ene']);
    $application = onboardingApplication($owner, $candidate);

    $draft = app(CimDraftGenerator::class)->forApplication($application);

    expect($draft)->toContain('CONTRACT INDIVIDUAL DE MUNCA')
        ->toContain('Maria Ene')
        ->toContain('Sudor autorizat')
        ->toContain('perioada de proba');
});

it('does not duplicate the onboarding checklist on repeated accept', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $application = onboardingApplication($owner, $candidate);

    foreach (range(1, 2) as $ignored) {
        $this->actingAs($owner)->patch(route('employer.applications.status', $application), [
            'status' => ApplicationStatus::Accepted->value,
        ]);
    }

    expect(OnboardingChecklist::where('application_id', $application->id)->count())->toBe(1);
});
