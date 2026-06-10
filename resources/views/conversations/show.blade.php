<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Messages') }}</h2>
    </x-slot>

    @php
        $viewer = auth()->user();
        $conversation = $activeConversation;
        $other = \App\Http\Controllers\ConversationController::otherParticipant($conversation, $viewer);
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="surface flex h-[calc(100vh-12rem)] overflow-hidden p-0">
                @include('conversations.partials.sidebar')

                <section class="flex flex-1 flex-col bg-slate-50">
                    <header class="flex items-center gap-3 border-b border-slate-200 bg-white px-5 py-3">
                        <a href="{{ route('conversations.index') }}" class="grid h-9 w-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Inapoi">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5 7 10l5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <x-avatar :name="$other?->name ?? 'Utilizator'" :email="$other?->email" :size="40" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-950">{{ $other?->name ?? 'Utilizator' }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $conversation->application->job->title }} · {{ $conversation->application->job->company->name }}
                            </p>
                        </div>
                    </header>

                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-6" id="messages-scroll">
                        @forelse ($conversation->messages as $message)
                            @php($isMine = $message->sender_id === $viewer->id)
                            <div class="flex items-end gap-2 {{ $isMine ? 'flex-row-reverse' : 'flex-row' }}">
                                <x-avatar :name="$message->sender->name" :email="$message->sender->email" :size="32" />
                                <div class="max-w-md">
                                    <div @class([
                                        'rounded-2xl px-4 py-2.5 text-sm whitespace-pre-line',
                                        'rounded-br-md bg-emerald-600 text-white' => $isMine,
                                        'rounded-bl-md bg-white text-slate-900 ring-1 ring-slate-200' => ! $isMine,
                                    ])>{{ $message->body }}</div>
                                    <p class="mt-1 px-1 text-[11px] text-slate-400 {{ $isMine ? 'text-right' : 'text-left' }}">
                                        {{ $message->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="grid h-full place-items-center text-sm text-slate-500">Niciun mesaj inca. Scrie primul mesaj.</div>
                        @endforelse
                    </div>

                    <footer class="border-t border-slate-200 bg-white px-4 py-3">
                        @if (session('status'))
                            <p class="mb-2 text-xs font-medium text-emerald-700">Mesaj trimis.</p>
                        @endif
                        <form method="POST" action="{{ route('messages.store', $conversation) }}" class="flex items-end gap-2">
                            @csrf
                            <div class="flex-1">
                                <textarea name="body" rows="1" maxlength="5000" placeholder="Scrie un mesaj..."
                                    class="block max-h-32 w-full resize-none rounded-2xl border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                    oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-1 px-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-600 text-white hover:bg-emerald-700" aria-label="Trimite">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 10 14-6-6 14-2.5-5.5L3 10Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </form>
                    </footer>
                </section>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const el = document.getElementById('messages-scroll');
            if (el) { el.scrollTop = el.scrollHeight; }
        })();
    </script>
</x-app-layout>
