<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('candidate account deletion removes private profile and application cvs', function () {
    Storage::fake('local');

    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profileCvPath = "cvs/{$candidate->id}/profile.pdf";
    $applicationCvPath = "applications/1/snapshot.pdf";
    Storage::disk('local')->put($profileCvPath, 'profile cv contents');
    Storage::disk('local')->put($applicationCvPath, 'application cv contents');
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => $profileCvPath]);
    $job = Job::factory()->for(Company::factory())->create();

    Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'cv_path' => $applicationCvPath,
        'status' => ApplicationStatus::Submitted,
    ]);

    $this
        ->actingAs($candidate)
        ->delete('/profile', [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    Storage::disk('local')->assertMissing($profileCvPath);
    Storage::disk('local')->assertMissing($applicationCvPath);
});

test('employer account deletion removes private application cv snapshots for their jobs', function () {
    Storage::fake('local');

    $employer = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create();
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $applicationCvPath = "applications/1/snapshot.pdf";
    Storage::disk('local')->put($applicationCvPath, 'application cv contents');

    Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'cv_path' => $applicationCvPath,
        'status' => ApplicationStatus::Submitted,
    ]);

    $this
        ->actingAs($employer)
        ->delete('/profile', [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    Storage::disk('local')->assertMissing($applicationCvPath);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
