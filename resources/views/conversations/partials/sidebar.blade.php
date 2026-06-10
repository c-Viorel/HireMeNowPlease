@php
    $viewer = auth()->user();
@endphp

<aside class="flex w-full flex-col border-slate-200 bg-white md:w-80 md:border-r {{ ($activeConversation ?? null) ? 'hidden md:flex' : 'flex' }}">
    <div class="border-b border-slate-200 px-4 py-4">
        <h2 class="text-lg font-bold text-slate-950">Mesaje</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ $conversations->count() }} conversatii</p>
    </div>

    <div class="flex-1 overflow-y-auto">
        @if ($conversations->isEmpty())
            <div class="px-4 py-10 text-center text-sm text-slate-500">Nu exista conversatii inca.</div>
        @endif

        @foreach ($conversations as $conversation)
            @php
                $other = \App\Http\Controllers\ConversationController::otherParticipant($conversation, $viewer);
                $latest = $conversation->latestMessage;
                $isActive = ($activeConversation ?? null) && $activeConversation->id === $conversation->id;
                $unread = (int) ($conversation->unread_count ?? 0);
                $preview = $latest ? (($latest->sender_id === $viewer->id ? 'Tu: ' : '').$latest->body) : 'Nicio conversatie inca.';
                $timeLabel = optional($latest?->created_at ?? $conversation->created_at)->diffForHumans(null, true);
            @endphp
            <a href="{{ route('conversations.show', $conversation) }}"
               class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 {{ $isActive ? 'bg-emerald-50/70' : '' }}">
                <x-avatar :name="$other?->name ?? 'Utilizator'" :email="$other?->email" :size="44" class="mt-0.5" />

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $other?->name ?? 'Utilizator' }}</p>
                        <span class="shrink-0 text-[11px] text-slate-400">{{ $timeLabel }}</span>
                    </div>
                    <p class="truncate text-xs text-slate-500">{{ $conversation->application->job->title }}</p>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <p class="truncate text-xs text-slate-500">{{ $preview }}</p>
                        @if ($unread > 0 && ! $isActive)
                            <span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-emerald-600 px-1.5 text-[11px] font-bold text-white">{{ $unread }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</aside>
