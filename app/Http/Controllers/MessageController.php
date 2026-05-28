<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function store(MessageRequest $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('view', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->loadMissing(['application.candidate', 'application.job.company.owner']);
        $recipient = $conversation->application->candidate_id === $request->user()->id
            ? $conversation->application->job->company->owner
            : $conversation->application->candidate;

        $recipient->notify(NewMessageNotification::fromMessage($message));

        return back()->with('status', 'message-sent');
    }
}
