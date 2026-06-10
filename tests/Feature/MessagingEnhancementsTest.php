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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function messagingParticipants(): array
{
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
    ]);

    return [$candidate, $employer, $application];
}

it('shows a read receipt for messages the recipient has seen', function () {
    [$candidate, $employer, $application] = messagingParticipants();
    $conversation = Conversation::create(['application_id' => $application->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $employer->id,
        'body' => 'Salut, esti disponibil pentru interviu?',
        'read_at' => now(),
    ]);

    $this->actingAs($employer)
        ->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee('Citit');
});

it('shows delivered state for messages not yet read', function () {
    [$candidate, $employer, $application] = messagingParticipants();
    $conversation = Conversation::create(['application_id' => $application->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $employer->id,
        'body' => 'Mesaj necitit inca.',
        'read_at' => null,
    ]);

    $this->actingAs($employer)
        ->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee('Trimis');
});

it('lets a candidate start a conversation from their applications list', function () {
    [$candidate, $employer, $application] = messagingParticipants();

    $this->actingAs($candidate)
        ->get(route('candidate.applications.index'))
        ->assertOk()
        ->assertSee('Trimite mesaj');

    $this->actingAs($candidate)
        ->post(route('conversations.store', $application))
        ->assertRedirect();

    expect(Conversation::where('application_id', $application->id)->exists())->toBeTrue();
});
