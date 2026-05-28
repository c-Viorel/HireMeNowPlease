<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Applications') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100">
                    @forelse ($applications as $application)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $application->candidate->name }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $application->job->title }} · {{ $application->job->company->name }} ·
                                    {{ str($application->status->value)->replace('_', ' ')->title() }}
                                </p>
                                @if ($application->message)
                                    <p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ $application->message }}</p>
                                @endif
                            </div>
                            <a href="{{ route('employer.applications.show', $application) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Review</a>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-sm text-gray-600">No applications yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>

