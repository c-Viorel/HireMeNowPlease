<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Jobs') }}</h2>
            <a href="{{ route('employer.jobs.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">New job</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Job saved.</p>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100">
                    @forelse ($jobs as $job)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $job->title }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $job->company->name }} · {{ ucfirst($job->status->value) }} ·
                                    {{ $job->applications_count }} {{ str('application')->plural($job->applications_count) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-4 text-sm font-medium">
                                <a href="{{ route('employer.jobs.show', $job) }}" class="text-gray-600 hover:text-gray-900">View</a>
                                <a href="{{ route('employer.jobs.edit', $job) }}" class="text-indigo-600 hover:text-indigo-700">Edit</a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-sm text-gray-600">No jobs yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">{{ $jobs->links() }}</div>
        </div>
    </div>
</x-app-layout>
