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
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\NewApplicationNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Notification;

function createNotificationCandidateEmployerAndPublishedJob(): array
{
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create();
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    return [$candidate, $employer, $company, $job];
}

function createNotificationApplicationParticipants(): array
{
    [$candidate, $employer, $company, $job] = createNotificationCandidateEmployerAndPublishedJob();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $candidate->candidateProfile->id,
        'message' => 'Interested.',
        'status' => ApplicationStatus::Submitted,
    ]);

    return [$candidate, $employer, $company, $job, $application];
}

it('notifies the company owner when a candidate applies', function () {
    Notification::fake();
    [$candidate, $employer, $company, $job] = createNotificationCandidateEmployerAndPublishedJob();

    $this->actingAs($candidate)->post(route('jobs.apply', [$company, $job]), [
        'message' => 'I would like to apply.',
    ])->assertRedirect(route('candidate.applications.index'));

    $application = Application::firstOrFail();

    Notification::assertSentTo($employer, NewApplicationNotification::class, function ($notification) use ($application, $employer) {
        $payload = $notification->toArray($employer);
        $mail = $notification->toMail($employer);

        return $notification->via($employer) === ['mail', 'database']
            && $payload['application_id'] === $application->id
            && $payload['url'] === route('employer.applications.show', $application)
            && $mail->actionUrl === route('employer.applications.show', $application);
    });
    Notification::assertNotSentTo($candidate, NewApplicationNotification::class);
});

it('notifies the candidate when an employer changes application status', function () {
    Notification::fake();
    [$candidate, $employer, , , $application] = createNotificationApplicationParticipants();

    $this->actingAs($employer)->patch(route('employer.applications.status', $application), [
        'status' => ApplicationStatus::Interview->value,
    ])->assertRedirect();

    Notification::assertSentTo($candidate, ApplicationStatusChangedNotification::class, function ($notification) use ($application, $candidate) {
        $payload = $notification->toArray($candidate);
        $mail = $notification->toMail($candidate);

        return $notification->via($candidate) === ['mail', 'database']
            && $payload['application_id'] === $application->id
            && $payload['status'] === ApplicationStatus::Interview->value
            && $payload['url'] === route('candidate.applications.index')
            && $mail->actionUrl === route('candidate.applications.index');
    });
    Notification::assertNotSentTo($employer, ApplicationStatusChangedNotification::class);
});

it('does not notify the candidate when application status is unchanged', function () {
    Notification::fake();
    [$candidate, $employer, , , $application] = createNotificationApplicationParticipants();
    $application->update(['status' => ApplicationStatus::Viewed]);

    $this->actingAs($employer)->patch(route('employer.applications.status', $application), [
        'status' => ApplicationStatus::Viewed->value,
    ])->assertRedirect();

    Notification::assertNotSentTo($candidate, ApplicationStatusChangedNotification::class);
});

it('notifies only the other conversation participant after a message is created', function () {
    Notification::fake();
    [$candidate, $employer, , , $application] = createNotificationApplicationParticipants();
    $conversation = Conversation::create(['application_id' => $application->id]);
    $sensitiveBody = 'Private compensation details: current salary is 123456.';

    $this->actingAs($candidate)->post(route('messages.store', $conversation), [
        'body' => $sensitiveBody,
    ])->assertRedirect();

    Notification::assertSentTo($employer, NewMessageNotification::class, function ($notification) use ($conversation, $employer, $sensitiveBody) {
        $payload = $notification->toArray($employer);
        $mail = $notification->toMail($employer);
        $mailLines = implode("\n", [
            ...$mail->introLines,
            ...$mail->outroLines,
        ]);

        return $notification->via($employer) === ['mail', 'database']
            && $payload['conversation_id'] === $conversation->id
            && $payload['url'] === route('conversations.show', $conversation)
            && $mail->actionUrl === route('conversations.show', $conversation)
            && ! array_key_exists('excerpt', $payload)
            && ! str_contains(json_encode($payload), $sensitiveBody)
            && ! str_contains($mailLines, $sensitiveBody);
    });
    Notification::assertNotSentTo($candidate, NewMessageNotification::class);
});
