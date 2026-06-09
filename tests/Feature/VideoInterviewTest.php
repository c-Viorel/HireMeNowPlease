<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Models\VideoInterview;
use App\Support\Ai\InterviewTranscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function videoApplication(User $owner, User $candidate): Application
{
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Cunostinte solide de Laravel, MySQL, Redis.',
        'status' => JobStatus::Published,
    ]);
    $profile = $candidate->candidateProfile ?? CandidateProfile::factory()->for($candidate, 'user')->create();

    return Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Interview,
    ]);
}

it('lets an employer create a video interview kit with questions', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $application = videoApplication($owner, $candidate);

    $this->actingAs($owner)
        ->post(route('employer.applications.video.store', $application))
        ->assertRedirect();

    $interview = VideoInterview::where('application_id', $application->id)->first();

    expect($interview)->not->toBeNull()
        ->and($interview->answers()->count())->toBeGreaterThanOrEqual(3);
});

it('shows the video interview session to the candidate', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $application = videoApplication($owner, $candidate);

    $this->actingAs($owner)->post(route('employer.applications.video.store', $application));
    $interview = VideoInterview::where('application_id', $application->id)->first();

    $this->actingAs($candidate)
        ->get(route('candidate.video.show', $interview))
        ->assertOk()
        ->assertSee('Interviu video');
});

it('transcribes and summarizes a candidate answer', function () {
    $this->app->bind(InterviewTranscriber::class, fn () => new class extends InterviewTranscriber {
        public function transcribe(string $reference): array
        {
            return [
                'transcript' => 'Transcript de test pentru '.$reference,
                'summary' => 'Rezumat AI de test.',
            ];
        }
    });

    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $application = videoApplication($owner, $candidate);

    $this->actingAs($owner)->post(route('employer.applications.video.store', $application));
    $interview = VideoInterview::where('application_id', $application->id)->first();
    $answer = $interview->answers()->first();
    $answer->update(['video_path' => 'videos/test.webm']);

    $this->actingAs($owner)
        ->patch(route('employer.applications.video.transcribe', [$application, $answer]))
        ->assertRedirect();

    expect($answer->fresh()->transcript)->toContain('Transcript de test')
        ->and($answer->fresh()->summary)->toBe('Rezumat AI de test.');
});
