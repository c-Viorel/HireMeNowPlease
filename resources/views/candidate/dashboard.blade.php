<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Candidate Dashboard') }}
            </h2>
            <a href="{{ route('candidate.profile.edit') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                Edit profile
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Profile completion</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Keep your candidate profile current so employers can evaluate you quickly.
                            </p>
                        </div>
                        <div class="text-3xl font-semibold text-gray-900">{{ $profileCompletion }}%</div>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $profileCompletion }}%"></div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Recent applications</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($recentApplications as $application)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $application->job->title }}</p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $application->job->company->name }}</p>
                                    </div>
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        {{ ucfirst($application->status->value) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">No applications yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Recent conversations</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($recentConversations as $conversation)
                            <div class="px-6 py-4">
                                <p class="font-medium text-gray-900">
                                    Conversation about {{ $conversation->application->job->title }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $conversation->application->job->company->name }}
                                </p>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">No conversations yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
