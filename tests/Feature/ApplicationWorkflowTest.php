<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a verified candidate apply once and lets employer update status', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'I would like to apply.',
    ])->assertRedirect(route('candidate.applications.index'));

    $application = Application::firstOrFail();

    expect($application->candidate_profile_id)->toBe($profile->id)
        ->and($application->cv_path)->toBe('cvs/demo.pdf')
        ->and($application->status)->toBe(ApplicationStatus::Submitted);

    $this->actingAs($employer)->patch("/employer/applications/{$application->id}/status", [
        'status' => 'viewed',
    ])->assertRedirect();

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'status' => 'viewed',
    ]);
});

it('blocks duplicate applications to the same job', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
    ]);

    $this->actingAs($candidate)->from(route('jobs.show', [$company, $job]))
        ->post(route('jobs.apply', [$company, $job]), [
            'message' => 'Trying again.',
        ])->assertRedirect(route('jobs.show', [$company, $job]))
        ->assertSessionHasErrors('job');

    expect(Application::where('job_id', $job->id)->where('candidate_id', $candidate->id)->count())->toBe(1);
});

it('blocks candidates without a profile from applying', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->from(route('jobs.show', [$company, $job]))
        ->post(route('jobs.apply', [$company, $job]), [
            'message' => 'I need a profile first.',
        ])->assertRedirect(route('jobs.show', [$company, $job]))
        ->assertSessionHasErrors('candidate_profile');

    expect(Application::count())->toBe(0);
});

it('blocks non-owner employers from viewing or updating applications', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherEmployer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($owner, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
    ]);

    $this->actingAs($otherEmployer)->get("/employer/applications/{$application->id}")->assertForbidden();

    $this->actingAs($otherEmployer)->patch("/employer/applications/{$application->id}/status", [
        'status' => 'accepted',
    ])->assertForbidden();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Submitted);
});

it('shortlists an application once and marks it shortlisted', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Viewed,
    ]);

    $this->actingAs($employer)->post("/employer/applications/{$application->id}/shortlist")
        ->assertRedirect();
    $this->actingAs($employer)->post("/employer/applications/{$application->id}/shortlist")
        ->assertRedirect();

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'status' => 'shortlisted',
    ]);

    expect(Shortlist::where('company_id', $company->id)
        ->where('job_id', $job->id)
        ->where('candidate_id', $candidate->id)
        ->count())->toBe(1);
});

it('does not allow applying through the wrong company scoped job route', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)
        ->post("/companies/{$otherCompany->slug}/jobs/{$job->slug}/apply", [
            'message' => 'This route should not resolve.',
        ])->assertNotFound();

    expect(Application::count())->toBe(0);
});

