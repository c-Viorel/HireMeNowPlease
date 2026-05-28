<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $company->name }}</h2>
            <a href="{{ route('employer.companies.edit', $company) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-start gap-5">
                        @if ($company->logo_path)
                            <img src="{{ Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-16 w-16 rounded-md object-cover">
                        @endif
                        <div>
                            <p class="text-sm font-medium text-amber-800">{{ ucfirst($company->status) }}</p>
                            <p class="mt-2 text-gray-700">{{ $company->description ?: 'No description added yet.' }}</p>
                            <p class="mt-3 text-sm text-gray-600">{{ $company->location ?: 'No location set' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Jobs</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($company->jobs as $job)
                        <div class="px-6 py-4">
                            <a href="{{ route('employer.jobs.show', $job) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $job->title }}</a>
                            <p class="mt-1 text-sm text-gray-600">{{ ucfirst($job->status->value) }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-sm text-gray-600">No jobs yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
