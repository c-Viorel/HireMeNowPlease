<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Admin Dashboard') }}</h2>
            <div class="flex gap-4 text-sm font-medium">
                <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-700">Users</a>
                <a href="{{ route('admin.companies.index') }}" class="text-indigo-600 hover:text-indigo-700">Companies</a>
                <a href="{{ route('admin.jobs.index') }}" class="text-indigo-600 hover:text-indigo-700">Jobs</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($counts as $label => $count)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p class="text-sm font-medium uppercase tracking-wide text-gray-500">{{ str($label)->headline() }}</p>
                        <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $count }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
