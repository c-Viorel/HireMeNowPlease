<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Companies') }}</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Admin dashboard</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Company updated.</p>
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
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Owner</th>
                                <th class="px-4 py-3">Jobs</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($companies as $company)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $company->name }}</td>
                                    <td class="px-4 py-3">{{ $company->owner->name }}</td>
                                    <td class="px-4 py-3">{{ $company->jobs_count }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($company->status) }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach (['pending', 'approved', 'blocked'] as $status)
                                                    <option value="{{ $status }}" @selected($company->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-gray-600">No companies found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>
