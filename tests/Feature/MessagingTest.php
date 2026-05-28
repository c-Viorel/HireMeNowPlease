<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\User;

function createMessagingApplicationParticipants(): array
{
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'Interested.',
        'status' => ApplicationStatus::Submitted,
    ]);

    return [$candidate, $employer, $application];
}

it('lets application participants exchange messages', function () {
    [$candidate, $employer, $application] = createMessagingApplicationParticipants();

    $this->actingAs($employer)->post("/applications/{$application->id}/conversations")
        ->assertRedirect();

    $conversation = Conversation::firstOrFail();

    $this->actingAs($candidate)->post("/conversations/{$conversation->id}/messages", [
        'body' => 'Hello, I am available for an interview.',
    ])->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_id' => $candidate->id,
        'body' => 'Hello, I am available for an interview.',
    ]);
});

it('creates only one conversation per application', function () {
    [$candidate, $employer, $application] = createMessagingApplicationParticipants();

    $this->actingAs($candidate)->post("/applications/{$application->id}/conversations")
        ->assertRedirect();
    $this->actingAs($employer)->post("/applications/{$application->id}/conversations")
        ->assertRedirect();

    expect(Conversation::where('application_id', $application->id)->count())->toBe(1);
});

it('prevents non-participants from creating conversations', function () {
    [, , $application] = createMessagingApplicationParticipants();
    $outsider = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($outsider)->post("/applications/{$application->id}/conversations")
        ->assertForbidden();

    expect(Conversation::count())->toBe(0);
});

it('lists only conversations for the authenticated participant', function () {
    [$candidate, $employer, $application] = createMessagingApplicationParticipants();
    $visibleConversation = Conversation::create(['application_id' => $application->id]);

    $otherCandidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $otherProfile = CandidateProfile::factory()->for($otherCandidate, 'user')->create();
    $otherEmployer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherCompany = Company::factory()->for($otherEmployer, 'owner')->create(['name' => 'Hidden Co']);
    $otherJob = Job::factory()->for($otherCompany)->create(['title' => 'Hidden Job']);
    $otherApplication = Application::create([
        'job_id' => $otherJob->id,
        'candidate_id' => $otherCandidate->id,
        'candidate_profile_id' => $otherProfile->id,
        'status' => ApplicationStatus::Submitted,
    ]);
    $hiddenConversation = Conversation::create(['application_id' => $otherApplication->id]);

    Message::create([
        'conversation_id' => $visibleConversation->id,
        'sender_id' => $candidate->id,
        'body' => 'Visible message',
    ]);
    Message::create([
        'conversation_id' => $hiddenConversation->id,
        'sender_id' => $otherCandidate->id,
        'body' => 'Hidden message',
    ]);

    $this->actingAs($employer)->get('/conversations')
        ->assertOk()
        ->assertSee('Visible message')
        ->assertDontSee('Hidden message')
        ->assertDontSee('Hidden Job');
});

it('shows a participant conversation and marks received messages as read', function () {
    [$candidate, $employer, $application] = createMessagingApplicationParticipants();
    $conversation = Conversation::create(['application_id' => $application->id]);
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $employer->id,
        'body' => 'Can you interview tomorrow?',
    ]);

    $this->actingAs($candidate)->get("/conversations/{$conversation->id}")
        ->assertOk()
        ->assertSee('Can you interview tomorrow?');

    expect($message->fresh()->read_at)->not->toBeNull();
});

it('prevents non-participants from reading or sending messages', function () {
    [, $employer, $application] = createMessagingApplicationParticipants();
    $conversation = Conversation::create(['application_id' => $application->id]);
    $outsider = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($outsider)->get("/conversations/{$conversation->id}")
        ->assertForbidden();

    $this->actingAs($outsider)->post("/conversations/{$conversation->id}/messages", [
        'body' => 'I should not be here.',
    ])->assertForbidden();

    expect(Message::count())->toBe(0);

    $this->actingAs($employer)->post("/conversations/{$conversation->id}/messages", [
        'body' => '',
    ])->assertSessionHasErrors('body');
});
