<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employer Dashboard') }}
            </h2>
            <a href="{{ route('employer.jobs.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                Post a job
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Companies</h3>
                            <a href="{{ route('employer.companies.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Add</a>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($companies as $company)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $company->name }}</p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $company->active_jobs_count }} active jobs</p>
                                    </div>
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800">
                                        {{ ucfirst($company->status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">No companies yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Active jobs</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($activeJobs as $job)
                            <div class="px-6 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $job->title }}</p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $job->company->name }} · {{ $job->location ?: 'Location flexible' }}</p>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $job->applications_count }} {{ Str::plural('application', $job->applications_count) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">No active jobs yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Candidate experience health</h3>
                </div>
                <div class="grid gap-4 p-6 lg:grid-cols-3">
                    @forelse ($responseHealth as $health)
                        <x-insights.responsiveness-card :score="$health['score']" :label="$health['company']->name" compact="true" class="bg-amber-50" />
                    @empty
                        <p class="text-sm text-gray-600">No company response data yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Latest messages</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($latestMessages as $message)
                        <div class="px-6 py-4">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $message->sender->name }}</p>
                                    <p class="mt-1 text-sm text-gray-700">{{ $message->body }}</p>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ $message->conversation->application->job->title }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-sm text-gray-600">No messages yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
