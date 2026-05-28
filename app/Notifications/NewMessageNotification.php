<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $conversationId,
        private readonly string $senderName,
        private readonly string $jobTitle,
    ) {}

    public static function fromMessage(Message $message): self
    {
        $message->loadMissing(['sender', 'conversation.application.job']);

        return new self(
            $message->conversation_id,
            $message->sender->name,
            $message->conversation->application->job->title,
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
            ->subject('New message on HireMe')
            ->line($this->senderName.' sent a message about '.$this->jobTitle.'.')
            ->action('Open conversation', $this->conversationUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_name' => $this->senderName,
            'job_title' => $this->jobTitle,
            'url' => $this->conversationUrl(),
        ];
    }

    private function conversationUrl(): string
    {
        return route('conversations.show', $this->conversationId);
    }
}
