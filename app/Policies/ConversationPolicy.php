<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        $conversation->loadMissing('application.job.company');

        return $conversation->application->candidate_id === $user->id
            || $conversation->application->job->company->owner_id === $user->id;
    }
}
