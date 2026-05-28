<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Companies') }}</h2>
            <a href="{{ route('employer.companies.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">New company</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Company saved.</p>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100">
                    @forelse ($companies as $company)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                @if ($company->logo_path)
                                    <img src="{{ Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-12 w-12 rounded-md object-cover">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-sm font-semibold text-gray-600">{{ Str::of($company->name)->substr(0, 1) }}</div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900">{{ $company->name }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ ucfirst($company->status) }} · {{ $company->jobs_count }} {{ Str::plural('job', $company->jobs_count) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm font-medium">
                                <a href="{{ route('employer.companies.show', $company) }}" class="text-gray-600 hover:text-gray-900">View</a>
                                <a href="{{ route('employer.companies.edit', $company) }}" class="text-indigo-600 hover:text-indigo-700">Edit</a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-sm text-gray-600">No companies yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>
