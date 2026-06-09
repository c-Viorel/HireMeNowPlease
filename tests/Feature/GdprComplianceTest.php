<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Support\Privacy\DataExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function gdprApplication(User $candidate): Application
{
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);
    $profile = $candidate->candidateProfile ?? CandidateProfile::factory()->for($candidate, 'user')->create();

    return Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
    ]);
}

it('exports all candidate data as a structured array', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'name' => 'Ana Pop']);
    CandidateProfile::factory()->for($candidate, 'user')->create(['phone' => '0712345678']);
    gdprApplication($candidate);

    $export = app(DataExporter::class)->forUser($candidate);

    expect($export['user']['name'])->toBe('Ana Pop')
        ->and($export['profile']['phone'])->toBe('0712345678')
        ->and($export['applications'])->toHaveCount(1);
});

it('downloads the data export as json', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    CandidateProfile::factory()->for($candidate, 'user')->create();

    $this->actingAs($candidate)
        ->get(route('privacy.export'))
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertHeader('content-disposition', 'attachment; filename=hireme-datele-mele.json');
});

it('anonymizes the account on deletion request but keeps hiring records', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'name' => 'Ion Ionescu']);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['phone' => '0700111222']);
    $application = gdprApplication($candidate);

    $this->actingAs($candidate)
        ->delete(route('privacy.destroy'))
        ->assertRedirect();

    $candidate->refresh();
    $profile->refresh();

    expect($candidate->name)->toBe('Utilizator anonimizat')
        ->and($candidate->is_active)->toBeFalse()
        ->and($profile->phone)->toBeNull()
        ->and(Application::whereKey($application->id)->exists())->toBeTrue();
});

it('purges applications older than the retention window', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $application = gdprApplication($candidate);
    $application->forceFill(['updated_at' => now()->subMonths(24)])->save();

    $this->artisan('privacy:purge-applications', ['--months' => 18])->assertSuccessful();

    expect(Application::whereKey($application->id)->exists())->toBeFalse();
});
