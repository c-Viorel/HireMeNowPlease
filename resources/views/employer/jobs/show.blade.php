<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $job->title }}</h2>
            <a href="{{ route('employer.jobs.edit', $job) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-600">{{ $job->company->name }}</p>
                            <p class="mt-3 whitespace-pre-line text-gray-800">{{ $job->description }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ ucfirst($job->status->value) }}</span>
                    </div>
                    <dl class="mt-6 grid gap-4 text-sm text-gray-700 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="font-medium text-gray-900">Location</dt>
                            <dd class="mt-1">{{ $job->location ?: 'Flexible' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-900">Employment</dt>
                            <dd class="mt-1">{{ str($job->employment_type->value)->replace('_', ' ')->title() }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-900">Workplace</dt>
                            <dd class="mt-1">{{ str($job->workplace_type->value)->replace('_', ' ')->title() }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-900">Salary</dt>
                            <dd class="mt-1">
                                @if ($job->salary_min || $job->salary_max)
                                    {{ $job->salary_min ?: '0' }} - {{ $job->salary_max ?: 'open' }}
                                @else
                                    Not listed
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Applications</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($job->applications as $application)
                        <div class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $application->candidate->name }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ ucfirst($application->status->value) }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-sm text-gray-600">No applications yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
