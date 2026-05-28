<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lets a candidate update their profile and upload a cv', function () {
    Storage::fake();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'phone' => '0712345678',
        'location' => 'Bucuresti',
        'headline' => 'Laravel Developer',
        'summary' => 'I build marketplace products.',
        'skills' => 'PHP, Laravel, , MySQL',
        'cv' => UploadedFile::fake()->create('cv.pdf', 256, 'application/pdf'),
    ])->assertRedirect('/candidate/profile');

    $this->assertDatabaseHas('candidate_profiles', [
        'user_id' => $candidate->id,
        'headline' => 'Laravel Developer',
    ]);

    $profile = $candidate->fresh()->candidateProfile;

    expect($profile->skills)->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($profile->cv_path)->not->toBeNull()
        ->and($profile->cv_path)->toStartWith("cvs/{$candidate->id}/");

    Storage::assertExists($profile->cv_path);
});

it('preserves the current cv when updating without a new upload', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'headline' => 'Senior Laravel Developer',
        'skills' => 'PHP, Laravel',
    ])->assertRedirect('/candidate/profile');

    expect($candidate->fresh()->candidateProfile->cv_path)->toBe('cvs/demo.pdf');
});

it('rejects invalid candidate cv uploads', function () {
    Storage::fake();
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->from('/candidate/profile')->post('/candidate/profile', [
        'headline' => 'Laravel Developer',
        'cv' => UploadedFile::fake()->create('cv.exe', 100, 'application/x-msdownload'),
    ])->assertRedirect('/candidate/profile')
        ->assertSessionHasErrors('cv');

    $this->actingAs($candidate)->from('/candidate/profile')->post('/candidate/profile', [
        'headline' => 'Laravel Developer',
        'cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf'),
    ])->assertRedirect('/candidate/profile')
        ->assertSessionHasErrors('cv');
});

it('only verified candidates can access candidate dashboard and profile', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $unverifiedCandidate = User::factory()->unverified()->create(['role' => UserRole::Candidate]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->get('/candidate/dashboard')->assertOk();
    $this->actingAs($candidate)->get('/candidate/profile')->assertOk();

    $this->actingAs($unverifiedCandidate)->get('/candidate/dashboard')->assertRedirect('/verify-email');
    $this->actingAs($unverifiedCandidate)->get('/candidate/profile')->assertRedirect('/verify-email');

    $this->actingAs($employer)->get('/candidate/dashboard')->assertForbidden();
    $this->actingAs($employer)->get('/candidate/profile')->assertForbidden();

    $this->actingAs($admin)->get('/candidate/dashboard')->assertForbidden();
    $this->actingAs($admin)->get('/candidate/profile')->assertForbidden();
});

it('shows candidate dashboard profile completion and recent activity states', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Profile completion')
        ->assertSee('No applications yet')
        ->assertSee('No conversations yet');

    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create([
        'title' => 'Backend Engineer',
        'status' => JobStatus::Published,
    ]);
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'I am interested in this role.',
        'status' => ApplicationStatus::Submitted,
    ]);
    Conversation::create(['application_id' => $application->id]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Backend Engineer')
        ->assertSee('Submitted')
        ->assertSee('Conversation about Backend Engineer');
});
