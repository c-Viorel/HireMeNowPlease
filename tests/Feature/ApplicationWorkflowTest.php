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
use App\Support\ApplicationSubmissions;
use App\Support\Shortlists;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function duplicateApplicationQueryException(): QueryException
{
    return new QueryException(
        'sqlite',
        'insert into applications (job_id, candidate_id) values (?, ?)',
        [1, 1],
        new Exception('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: applications.job_id, applications.candidate_id')
    );
}

function duplicateShortlistQueryException(): QueryException
{
    return new QueryException(
        'sqlite',
        'insert into shortlists (company_id, job_id, candidate_id) values (?, ?, ?)',
        [1, 1, 1],
        new Exception('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: shortlists.company_id, shortlists.job_id, shortlists.candidate_id')
    );
}

function applicationWorkflowApprovedCompany(?User $owner = null, array $companyAttributes = [], array $ownerAttributes = []): Company
{
    $owner ??= User::factory()->create([
        'role' => UserRole::Employer,
        'email_verified_at' => now(),
        ...$ownerAttributes,
    ]);

    return Company::factory()
        ->for($owner, 'owner')
        ->create([
            'status' => 'approved',
            ...$companyAttributes,
        ]);
}

it('lets a verified candidate apply once and lets employer update status', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    Storage::disk('local')->put("cvs/{$candidate->id}/demo.pdf", 'private cv contents');
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => "cvs/{$candidate->id}/demo.pdf"]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = applicationWorkflowApprovedCompany($employer);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'I would like to apply.',
    ])->assertRedirect(route('candidate.applications.index'));

    $application = Application::firstOrFail();

    expect($application->candidate_profile_id)->toBe($profile->id)
        ->and($application->cv_path)->toStartWith("applications/{$application->id}/")
        ->and($application->cv_path)->not->toBe($profile->cv_path)
        ->and($application->status)->toBe(ApplicationStatus::Submitted);

    Storage::disk('local')->assertExists($application->cv_path);

    $this->actingAs($employer)->patch("/employer/applications/{$application->id}/status", [
        'status' => 'viewed',
    ])->assertRedirect();

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'status' => 'viewed',
    ]);
});

it('lets the owning employer download the captured application cv', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    Storage::disk('local')->put("cvs/{$candidate->id}/resume.pdf", 'captured cv contents');
    CandidateProfile::factory()->for($candidate, 'user')->create([
        'cv_path' => "cvs/{$candidate->id}/resume.pdf",
    ]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = applicationWorkflowApprovedCompany($employer);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'Please review my CV.',
    ]);

    $application = Application::firstOrFail();

    $this->actingAs($employer)->get(route('employer.applications.cv', $application))
        ->assertOk()
        ->assertDownload('resume.pdf');
});

it('forbids non-owner employers from downloading application cvs', function () {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherEmployer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($owner, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    Storage::disk('local')->put('applications/1/resume.pdf', 'captured cv contents');
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'cv_path' => 'applications/1/resume.pdf',
        'status' => ApplicationStatus::Submitted,
    ]);

    $this->actingAs($otherEmployer)->get(route('employer.applications.cv', $application))
        ->assertForbidden();
});

it('hides raw cv storage paths on the employer application page', function () {
    Storage::fake('local');
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    Storage::disk('local')->put('applications/1/resume.pdf', 'captured cv contents');
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'cv_path' => 'applications/1/resume.pdf',
        'status' => ApplicationStatus::Submitted,
    ]);

    $this->actingAs($employer)->get(route('employer.applications.show', $application))
        ->assertOk()
        ->assertSee('Download CV')
        ->assertDontSee('applications/1/resume.pdf');
});

it('keeps application cv downloads working after the candidate replaces their profile cv', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    Storage::disk('local')->put("cvs/{$candidate->id}/old.pdf", 'original cv snapshot');
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create([
        'cv_path' => "cvs/{$candidate->id}/old.pdf",
    ]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = applicationWorkflowApprovedCompany($employer);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'Snapshot this CV.',
    ]);

    $application = Application::firstOrFail();
    $snapshotPath = $application->cv_path;

    Storage::disk('local')->delete($profile->cv_path);
    Storage::disk('local')->put("cvs/{$candidate->id}/new.pdf", 'new profile cv');
    $profile->update(['cv_path' => "cvs/{$candidate->id}/new.pdf"]);

    Storage::disk('local')->assertExists($snapshotPath);

    $this->actingAs($employer)->get(route('employer.applications.cv', $application))
        ->assertOk()
        ->assertDownload('old.pdf');
});

it('deletes captured cv snapshots when applications are deleted', function () {
    Storage::fake('local');

    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $snapshotPath = 'applications/1/resume.pdf';
    Storage::disk('local')->put($snapshotPath, 'captured cv contents');
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'cv_path' => $snapshotPath,
        'status' => ApplicationStatus::Submitted,
    ]);

    $application->delete();

    Storage::disk('local')->assertMissing($snapshotPath);
});

it('blocks duplicate applications to the same job', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = applicationWorkflowApprovedCompany();
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

it('converts duplicate application query exceptions into validation errors', function () {
    $this->expectException(ValidationException::class);

    try {
        ApplicationSubmissions::create([
            'job_id' => 1,
            'candidate_id' => 1,
            'candidate_profile_id' => 1,
            'status' => ApplicationStatus::Submitted,
        ], fn () => throw duplicateApplicationQueryException());
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            'job' => ['You have already applied to this job.'],
        ]);

        throw $exception;
    }
});

it('blocks candidates without a profile from applying', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $company = applicationWorkflowApprovedCompany();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->from(route('jobs.show', [$company, $job]))
        ->post(route('jobs.apply', [$company, $job]), [
            'message' => 'I need a profile first.',
        ])->assertRedirect(route('jobs.show', [$company, $job]))
        ->assertSessionHasErrors('candidate_profile');

    expect(Application::count())->toBe(0);
});

it('blocks applications to published jobs when the company is blocked or employer is inactive', function (array $companyAttributes, array $ownerAttributes) {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = applicationWorkflowApprovedCompany(null, $companyAttributes, $ownerAttributes);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)
        ->post(route('jobs.apply', [$company, $job]), [
            'message' => 'This job should no longer accept applications.',
        ])->assertNotFound();

    expect(Application::count())->toBe(0);
})->with([
    'blocked company' => [['status' => 'blocked'], []],
    'inactive employer' => [[], ['is_active' => false]],
]);

it('blocks inactive candidates from applying', function () {
    $candidate = User::factory()->create([
        'role' => UserRole::Candidate,
        'email_verified_at' => now(),
        'is_active' => false,
    ]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = applicationWorkflowApprovedCompany();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->from(route('jobs.show', [$company, $job]))
        ->post(route('jobs.apply', [$company, $job]), [
            'message' => 'I should not be able to apply.',
        ])->assertRedirect('/login')
        ->assertSessionHasErrors('email');

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

it('treats duplicate shortlist query exceptions as successful idempotent creation', function () {
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

    Shortlists::createForApplication($application, fn () => throw duplicateShortlistQueryException());

    expect(true)->toBeTrue();
});

it('does not allow applying through the wrong company scoped job route', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = applicationWorkflowApprovedCompany();
    $otherCompany = applicationWorkflowApprovedCompany();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)
        ->post("/companies/{$otherCompany->slug}/jobs/{$job->slug}/apply", [
            'message' => 'This route should not resolve.',
        ])->assertNotFound();

    expect(Application::count())->toBe(0);
});
