<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $applicationId,
        private readonly string $candidateName,
        private readonly string $jobTitle,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $application->loadMissing(['candidate', 'job']);

        return new self(
            $application->id,
            $application->candidate->name,
            $application->job->title,
        );
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New application received')
            ->line($this->candidateName.' applied for '.$this->jobTitle.'.')
            ->action('Review application', $this->applicationUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->applicationId,
            'candidate_name' => $this->candidateName,
            'job_title' => $this->jobTitle,
            'url' => $this->applicationUrl(),
        ];
    }

    private function applicationUrl(): string
    {
        return route('employer.applications.show', $this->applicationId);
    }
}
