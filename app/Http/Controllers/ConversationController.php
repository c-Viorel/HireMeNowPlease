<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->with(['application.candidate', 'application.job.company', 'latestMessage.sender'])
            ->withMax('messages as last_message_at', 'created_at')
            ->whereHas('application', function ($query) use ($user) {
                $query->where('candidate_id', $user->id)
                    ->orWhereHas('job.company', fn ($companyQuery) => $companyQuery->where('owner_id', $user->id));
            })
            ->orderByRaw('COALESCE(last_message_at, conversations.created_at) DESC')
            ->paginate(10);

        return view('conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        $application->loadMissing('job.company');

        abort_unless($this->participatesInApplication($request->user(), $application), 403);

        $conversation = $this->firstOrCreateConversation($application);

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'conversation-ready');
    }

    public function show(Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('conversations.show', [
            'conversation' => $conversation->load([
                'application.candidate',
                'application.job.company',
                'messages' => fn ($query) => $query->oldest(),
                'messages.sender',
            ]),
        ]);
    }

    private function participatesInApplication(User $user, Application $application): bool
    {
        return $application->candidate_id === $user->id
            || $application->job->company->owner_id === $user->id;
    }

    private function firstOrCreateConversation(Application $application): Conversation
    {
        try {
            return Conversation::firstOrCreate([
                'application_id' => $application->id,
            ]);
        } catch (QueryException $exception) {
            $conversation = Conversation::where('application_id', $application->id)->first();

            if ($conversation) {
                return $conversation;
            }

            throw $exception;
        }
    }
}
