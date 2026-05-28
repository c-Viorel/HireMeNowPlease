<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ApplicationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $applicationId,
        private readonly string $jobTitle,
        private readonly string $status,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $application->loadMissing('job');
        $status = $application->status instanceof ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        return new self(
            $application->id,
            $application->job->title,
            $status,
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
            ->subject('Application status updated')
            ->line('Your application for '.$this->jobTitle.' is now '.$this->displayStatus().'.')
            ->action('View applications', $this->applicationsUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->applicationId,
            'job_title' => $this->jobTitle,
            'status' => $this->status,
            'url' => $this->applicationsUrl(),
        ];
    }

    private function applicationsUrl(): string
    {
        return route('candidate.applications.index');
    }

    private function displayStatus(): string
    {
        return Str::headline($this->status);
    }
}
