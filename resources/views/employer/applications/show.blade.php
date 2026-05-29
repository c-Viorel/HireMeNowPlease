<x-app-layout>
    @php
        $profileSnapshot = $application->profile_snapshot ?: $application->candidateProfile?->snapshot();
        $experiences = $profileSnapshot['experiences'] ?? [];
        $educations = $profileSnapshot['educations'] ?? [];
        $certifications = $profileSnapshot['certifications'] ?? [];
        $links = $profileSnapshot['links'] ?? [];
        $jobPreference = $profileSnapshot['job_preference'] ?? null;
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('M Y') : null;
    @endphp

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
                            <div><dt class="font-medium text-gray-900">Headline</dt><dd>{{ $profileSnapshot['headline'] ?? 'Not provided' }}</dd></div>
                            <div><dt class="font-medium text-gray-900">Location</dt><dd>{{ $profileSnapshot['location'] ?? 'Not provided' }}</dd></div>
                            <div><dt class="font-medium text-gray-900">Phone</dt><dd>{{ $profileSnapshot['phone'] ?? 'Not provided' }}</dd></div>
                            @if (! empty($profileSnapshot['skills']))
                                <div>
                                    <dt class="font-medium text-gray-900">Skills</dt>
                                    <dd class="mt-1 flex flex-wrap gap-2">
                                        @foreach ($profileSnapshot['skills'] as $skill)
                                            <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $skill }}</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                            <div>
                                <dt class="font-medium text-gray-900">CV</dt>
                                <dd>
                                    @if ($application->cv_path)
                                        <a href="{{ route('employer.applications.cv', $application) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Download CV</a>
                                    @else
                                        No CV captured
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Message</h4>
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-600">{{ $application->message ?: 'No message included.' }}</p>

                        @if ($jobPreference)
                            <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
                                <h4 class="font-semibold text-gray-900">Preferences</h4>
                                <dl class="mt-3 space-y-2">
                                    @if ($jobPreference['availability'] ?? null)
                                        <div><dt class="font-medium text-gray-900">Availability</dt><dd>{{ $jobPreference['availability'] }}</dd></div>
                                    @endif
                                    @if ($jobPreference['experience_level'] ?? null)
                                        <div><dt class="font-medium text-gray-900">Level</dt><dd>{{ str($jobPreference['experience_level'])->title() }}</dd></div>
                                    @endif
                                    @if (($jobPreference['desired_salary_min'] ?? null) || ($jobPreference['desired_salary_max'] ?? null))
                                        <div><dt class="font-medium text-gray-900">Desired salary</dt><dd>{{ number_format($jobPreference['desired_salary_min'] ?? 0) }} - {{ number_format($jobPreference['desired_salary_max'] ?? 0) }} RON</dd></div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Experience</h3>
                <div class="mt-5 space-y-5">
                    @forelse ($experiences as $experience)
                        <article class="border-l-2 border-indigo-200 pl-4">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $experience['title'] ?? 'Untitled role' }}</h4>
                                    <p class="text-sm text-gray-600">{{ $experience['company'] ?? 'Company not provided' }}</p>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ $formatDate($experience['start_date'] ?? null) ?: 'Start not provided' }}
                                    -
                                    {{ ($experience['is_current'] ?? false) ? 'Present' : ($formatDate($experience['end_date'] ?? null) ?: 'End not provided') }}
                                </p>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ str($experience['employment_type'] ?? '')->replace('_', ' ')->title() }}
                                @if ($experience['location'] ?? null)
                                    · {{ $experience['location'] }}
                                @endif
                                @if ($experience['workplace_type'] ?? null)
                                    · {{ str($experience['workplace_type'])->replace('_', ' ')->title() }}
                                @endif
                            </p>
                            @if ($experience['description'] ?? null)
                                <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $experience['description'] }}</p>
                            @endif
                            @if (! empty($experience['skills']))
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($experience['skills'] as $skill)
                                        <span class="rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-600">No structured experience added.</p>
                    @endforelse
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Education</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($educations as $education)
                            <article>
                                <h4 class="font-semibold text-gray-900">{{ $education['institution'] ?? 'Institution not provided' }}</h4>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $education['degree'] ?? 'Degree not provided' }}
                                    @if ($education['field_of_study'] ?? null)
                                        · {{ $education['field_of_study'] }}
                                    @endif
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $formatDate($education['start_date'] ?? null) ?: 'Start not provided' }}
                                    -
                                    {{ ($education['is_current'] ?? false) ? 'Present' : ($formatDate($education['end_date'] ?? null) ?: 'End not provided') }}
                                </p>
                                @if ($education['description'] ?? null)
                                    <p class="mt-2 text-sm text-gray-700">{{ $education['description'] }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-gray-600">No education added.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Credentials and links</h3>
                    <div class="mt-5 space-y-5">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Certifications</h4>
                            <div class="mt-3 space-y-3">
                                @forelse ($certifications as $certification)
                                    <article class="text-sm text-gray-600">
                                        <p class="font-medium text-gray-900">{{ $certification['name'] ?? 'Certification' }}</p>
                                        <p>{{ $certification['issuer'] ?? 'Issuer not provided' }}</p>
                                        @if ($certification['credential_url'] ?? null)
                                            <a href="{{ $certification['credential_url'] }}" class="font-medium text-indigo-600 hover:text-indigo-700" target="_blank" rel="noreferrer">Credential</a>
                                        @endif
                                    </article>
                                @empty
                                    <p class="text-sm text-gray-600">No certifications added.</p>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Links</h4>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($links as $link)
                                    <a href="{{ $link['url'] }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50" target="_blank" rel="noreferrer">{{ $link['label'] }}</a>
                                @empty
                                    <p class="text-sm text-gray-600">No links added.</p>
                                @endforelse
                            </div>
                        </div>
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
