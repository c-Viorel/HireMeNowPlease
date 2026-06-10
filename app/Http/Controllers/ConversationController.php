<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        return view('conversations.index', [
            'conversations' => $this->conversationList($request->user()),
            'activeConversation' => null,
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

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('conversations.show', [
            'conversations' => $this->conversationList($request->user()),
            'activeConversation' => $conversation->load([
                'application.candidate',
                'application.job.company.owner',
                'messages' => fn ($query) => $query->oldest(),
                'messages.sender',
            ]),
        ]);
    }

    /**
     * Resolve the participant on the other side of a conversation for the
     * given viewer (candidate sees the employer, employer sees the candidate).
     */
    public static function otherParticipant(Conversation $conversation, User $viewer): ?User
    {
        $application = $conversation->application;

        if ($application === null) {
            return null;
        }

        if ($application->candidate_id === $viewer->id) {
            return $application->job?->company?->owner;
        }

        return $application->candidate;
    }

    /**
     * @return Collection<int, Conversation>
     */
    private function conversationList(User $user)
    {
        return Conversation::query()
            ->with(['application.candidate', 'application.job.company.owner', 'latestMessage.sender'])
            ->withMax('messages as last_message_at', 'created_at')
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->whereHas('application', function ($query) use ($user) {
                $query->where('candidate_id', $user->id)
                    ->orWhereHas('job.company', fn ($companyQuery) => $companyQuery->where('owner_id', $user->id));
            })
            ->orderByRaw('COALESCE(last_message_at, conversations.created_at) DESC')
            ->get();
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
