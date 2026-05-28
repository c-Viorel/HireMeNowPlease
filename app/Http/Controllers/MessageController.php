<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function store(MessageRequest $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('view', $conversation);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return back()->with('status', 'message-sent');
    }
}
