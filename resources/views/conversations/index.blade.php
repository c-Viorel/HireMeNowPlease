<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Messages') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100">
                    @forelse ($conversations as $conversation)
                        @php
                            $application = $conversation->application;
                            $latestMessage = $conversation->messages->sortByDesc('created_at')->first();
                        @endphp

                        <a href="{{ route('conversations.show', $conversation) }}" class="block px-6 py-5 hover:bg-gray-50">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $application->job->title }}</p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $application->job->company->name }} · {{ $application->candidate->name }}
                                    </p>
                                    <p class="mt-3 line-clamp-2 text-sm text-gray-600">
                                        {{ $latestMessage?->body ?? 'No messages yet.' }}
                                    </p>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ optional($latestMessage?->created_at ?? $conversation->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-10 text-sm text-gray-600">No conversations yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">{{ $conversations->links() }}</div>
        </div>
    </div>
</x-app-layout>
