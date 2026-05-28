<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Conversation') }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $conversation->application->job->title }} · {{ $conversation->application->job->company->name }}
                </p>
            </div>
            <a href="{{ route('conversations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Back to messages</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Message sent.</p>
            @endif

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="space-y-4">
                    @forelse ($conversation->messages as $message)
                        @php($isMine = $message->sender_id === auth()->id())
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-2xl rounded-lg px-4 py-3 {{ $isMine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900' }}">
                                <div class="flex items-center gap-2 text-xs {{ $isMine ? 'text-indigo-100' : 'text-gray-500' }}">
                                    <span>{{ $message->sender->name }}</span>
                                    <span>{{ $message->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm">{{ $message->body }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">No messages yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('messages.store', $conversation) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea id="body" name="body" rows="5" maxlength="5000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Send message</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
