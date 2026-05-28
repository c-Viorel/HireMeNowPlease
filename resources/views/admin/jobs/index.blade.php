<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Jobs') }}</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Admin dashboard</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Job updated.</p>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                            <tr>
                                <th class="px-4 py-3">Job</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Applications</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Published</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($jobs as $job)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $job->title }}</td>
                                    <td class="px-4 py-3">{{ $job->company->name }}</td>
                                    <td class="px-4 py-3">{{ $job->applications_count }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($job->status->value) }}</td>
                                    <td class="px-4 py-3">{{ $job->published_at?->toFormattedDateString() ?? 'Not published' }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('admin.jobs.update', $job) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach (['pending', 'published', 'closed', 'rejected'] as $status)
                                                    <option value="{{ $status }}" @selected($job->status->value === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-gray-600">No jobs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $jobs->links() }}</div>
        </div>
    </div>
</x-app-layout>
