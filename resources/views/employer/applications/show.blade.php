<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Application review') }}</h2>
            <a href="{{ route('employer.applications.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Back to applications</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Application updated.</p>
            @endif

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-600">{{ $application->job->company->name }} · {{ $application->job->title }}</p>
                        <h3 class="mt-2 text-2xl font-semibold text-gray-900">{{ $application->candidate->name }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Status: {{ str($application->status->value)->replace('_', ' ')->title() }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('employer.applications.shortlist', $application) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Shortlist</button>
                    </form>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Candidate profile</h4>
                        <dl class="mt-3 space-y-2 text-sm text-gray-600">
                            <div><dt class="font-medium text-gray-900">Headline</dt><dd>{{ $application->candidateProfile->headline ?: 'Not provided' }}</dd></div>
                            <div><dt class="font-medium text-gray-900">Location</dt><dd>{{ $application->candidateProfile->location ?: 'Not provided' }}</dd></div>
                            <div><dt class="font-medium text-gray-900">Phone</dt><dd>{{ $application->candidateProfile->phone ?: 'Not provided' }}</dd></div>
                            <div><dt class="font-medium text-gray-900">CV</dt><dd>{{ $application->cv_path ?: 'No CV captured' }}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Message</h4>
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-600">{{ $application->message ?: 'No message included.' }}</p>
                    </div>
                </div>
            </section>

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Update pipeline status</h3>

                <form method="POST" action="{{ route('employer.applications.status', $application) }}" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')

                    <div class="sm:w-72">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($application->status->value === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save status</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>

