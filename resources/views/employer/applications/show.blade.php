<x-app-layout>
    @php
        $profileSnapshot = $application->profile_snapshot ?: $application->candidateProfile?->snapshot();
        $experiences = $profileSnapshot['experiences'] ?? [];
        $educations = $profileSnapshot['educations'] ?? [];
        $certifications = $profileSnapshot['certifications'] ?? [];
        $links = $profileSnapshot['links'] ?? [];
        $jobPreference = $profileSnapshot['job_preference'] ?? null;
        $scorecard = $application->scorecard;
        $scorecardItems = $scorecard?->items?->keyBy('criterion') ?? collect();
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('M Y') : null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Application review</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $application->candidate->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $application->job->company->name }} · {{ $application->job->title }}</p>
            </div>
            <a href="{{ route('employer.applications.index') }}" class="btn-secondary">Back to applications</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <main class="space-y-6">
                @if (session('status'))
                    <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Application updated.</p>
                @endif

                <section class="surface p-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <span class="chip">{{ str($application->status->value)->replace('_', ' ')->title() }}</span>
                            <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $application->candidate->name }}</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $profileSnapshot['headline'] ?? 'Headline not provided' }}</p>
                        </div>
                        <form method="POST" action="{{ route('employer.applications.shortlist', $application) }}">
                            @csrf
                            <button type="submit" class="btn-primary">Shortlist</button>
                        </form>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</p>
                            <p class="mt-1 text-sm font-semibold text-slate-950">{{ $profileSnapshot['location'] ?? 'Not provided' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Phone</p>
                            <p class="mt-1 text-sm font-semibold text-slate-950">{{ $profileSnapshot['phone'] ?? 'Not provided' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">CV</p>
                            <p class="mt-1 text-sm font-semibold">
                                @if ($application->cv_path)
                                    <a href="{{ route('employer.applications.cv', $application) }}" class="text-emerald-700 hover:text-emerald-800">Download CV</a>
                                @else
                                    No CV captured
                                @endif
                            </p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Applied</p>
                            <p class="mt-1 text-sm font-semibold text-slate-950">{{ $application->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    @if (! empty($profileSnapshot['skills']))
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($profileSnapshot['skills'] as $skill)
                                <span class="chip">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                </section>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-insights.job-fit-card :fit-score="$fitScore" title="Explainable Fit Score" />
                    <x-insights.hr-copilot-card :brief="$copilotBrief" />
                </div>

                <details class="surface group p-6" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">Experience</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ count($experiences) }} roluri structurate</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 group-open:hidden">Open</span>
                    </summary>
                    <div class="mt-5 space-y-5">
                        @forelse ($experiences as $experience)
                            <article class="border-l-2 border-emerald-200 pl-4">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="font-semibold text-slate-950">{{ $experience['title'] ?? 'Untitled role' }}</h4>
                                        <p class="text-sm text-slate-600">{{ $experience['company'] ?? 'Company not provided' }}</p>
                                    </div>
                                    <p class="text-sm text-slate-500">
                                        {{ $formatDate($experience['start_date'] ?? null) ?: 'Start not provided' }}
                                        -
                                        {{ ($experience['is_current'] ?? false) ? 'Present' : ($formatDate($experience['end_date'] ?? null) ?: 'End not provided') }}
                                    </p>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ str($experience['employment_type'] ?? '')->replace('_', ' ')->title() }}
                                    @if ($experience['location'] ?? null)
                                        · {{ $experience['location'] }}
                                    @endif
                                    @if ($experience['workplace_type'] ?? null)
                                        · {{ str($experience['workplace_type'])->replace('_', ' ')->title() }}
                                    @endif
                                </p>
                                @if ($experience['description'] ?? null)
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $experience['description'] }}</p>
                                @endif
                                @if (! empty($experience['skills']))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($experience['skills'] as $skill)
                                            <span class="chip-emerald">{{ $skill }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-slate-600">No structured experience added.</p>
                        @endforelse
                    </div>
                </details>

                <section class="grid gap-6 lg:grid-cols-2">
                    <details class="surface group p-6" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-950">Education</h3>
                            <span class="text-sm font-semibold text-emerald-700 group-open:hidden">Open</span>
                        </summary>
                        <div class="mt-5 space-y-4">
                            @forelse ($educations as $education)
                                <article>
                                    <h4 class="font-semibold text-slate-950">{{ $education['institution'] ?? 'Institution not provided' }}</h4>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $education['degree'] ?? 'Degree not provided' }}
                                        @if ($education['field_of_study'] ?? null)
                                            · {{ $education['field_of_study'] }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $formatDate($education['start_date'] ?? null) ?: 'Start not provided' }}
                                        -
                                        {{ ($education['is_current'] ?? false) ? 'Present' : ($formatDate($education['end_date'] ?? null) ?: 'End not provided') }}
                                    </p>
                                    @if ($education['description'] ?? null)
                                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $education['description'] }}</p>
                                    @endif
                                </article>
                            @empty
                                <p class="text-sm text-slate-600">No education added.</p>
                            @endforelse
                        </div>
                    </details>

                    <details class="surface group p-6" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-950">Credentials and links</h3>
                            <span class="text-sm font-semibold text-emerald-700 group-open:hidden">Open</span>
                        </summary>
                        <div class="mt-5 space-y-5">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-950">Certifications</h4>
                                <div class="mt-3 space-y-3">
                                    @forelse ($certifications as $certification)
                                        <article class="text-sm text-slate-600">
                                            <p class="font-medium text-slate-950">{{ $certification['name'] ?? 'Certification' }}</p>
                                            <p>{{ $certification['issuer'] ?? 'Issuer not provided' }}</p>
                                            @if ($certification['credential_url'] ?? null)
                                                <a href="{{ $certification['credential_url'] }}" class="font-medium text-emerald-700 hover:text-emerald-800" target="_blank" rel="noreferrer">Credential</a>
                                            @endif
                                        </article>
                                    @empty
                                        <p class="text-sm text-slate-600">No certifications added.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-slate-950">Links</h4>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse ($links as $link)
                                        <a href="{{ $link['url'] }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50" target="_blank" rel="noreferrer">{{ $link['label'] }}</a>
                                    @empty
                                        <p class="text-sm text-slate-600">No links added.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </details>
                </section>

                <section class="surface p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">Structured interview scorecard</h3>
                            <p class="mt-1 text-sm text-slate-600">Evalueaza consecvent candidatii pe criterii comune, cu dovezi din interviu.</p>
                        </div>
                        @if ($scorecard)
                            <div class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900">
                                {{ $scorecard->overall_score }}% · {{ str($scorecard->recommendation)->replace('_', ' ')->title() }}
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('employer.applications.scorecard', $application) }}" class="mt-5 space-y-5">
                        @csrf

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="recommendation" class="field-label">Recommendation</label>
                                <select id="recommendation" name="recommendation" class="field-control">
                                    @foreach (['strong_yes' => 'Strong yes', 'yes' => 'Yes', 'hold' => 'Hold', 'no' => 'No'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('recommendation', $scorecard?->recommendation ?? 'hold') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('recommendation')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="notes" class="field-label">Overall notes</label>
                                <textarea id="notes" name="notes" rows="3" class="field-control">{{ old('notes', $scorecard?->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            @foreach ($scorecardCriteria as $index => $criterion)
                                @php($item = $scorecardItems->get($criterion))
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <input type="hidden" name="items[{{ $index }}][criterion]" value="{{ $criterion }}">
                                    <div class="grid gap-4 md:grid-cols-[1fr_9rem]">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950">{{ $criterion }}</p>
                                            <label for="items_{{ $index }}_evidence" class="sr-only">Evidence</label>
                                            <textarea id="items_{{ $index }}_evidence" name="items[{{ $index }}][evidence]" rows="2" class="field-control" placeholder="Evidence from interview">{{ old("items.$index.evidence", $item?->evidence) }}</textarea>
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_score" class="field-label">Score</label>
                                            <select id="items_{{ $index }}_score" name="items[{{ $index }}][score]" class="field-control">
                                                @for ($score = 1; $score <= 5; $score++)
                                                    <option value="{{ $score }}" @selected((int) old("items.$index.score", $item?->score ?? 3) === $score)>{{ $score }}/5</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn-primary">Save scorecard</button>
                    </form>
                </section>
            </main>

            <aside class="order-first space-y-6 lg:order-none lg:sticky lg:top-20 lg:self-start">
                <section class="surface p-5">
                    <p class="text-sm font-semibold text-slate-950">Decision rail</p>
                    <div class="mt-4 grid gap-3">
                        <div class="rounded-lg bg-sky-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-sky-700">Fit score</p>
                            <p class="mt-1 text-3xl font-bold text-sky-950">{{ $fitScore['score'] ?? 0 }}%</p>
                            <p class="mt-1 text-sm text-sky-800">{{ $fitScore['label'] ?? 'No score' }}</p>
                        </div>
                        @if ($jobPreference)
                            <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                                <h4 class="font-semibold text-slate-950">Preferences</h4>
                                <dl class="mt-3 space-y-2">
                                    @if ($jobPreference['availability'] ?? null)
                                        <div><dt class="font-medium text-slate-950">Availability</dt><dd>{{ $jobPreference['availability'] }}</dd></div>
                                    @endif
                                    @if ($jobPreference['experience_level'] ?? null)
                                        <div><dt class="font-medium text-slate-950">Level</dt><dd>{{ str($jobPreference['experience_level'])->title() }}</dd></div>
                                    @endif
                                    @if (($jobPreference['desired_salary_min'] ?? null) || ($jobPreference['desired_salary_max'] ?? null))
                                        <div><dt class="font-medium text-slate-950">Desired salary</dt><dd>{{ number_format($jobPreference['desired_salary_min'] ?? 0) }} - {{ number_format($jobPreference['desired_salary_max'] ?? 0) }} RON</dd></div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="surface p-5">
                    <h3 class="text-base font-semibold text-slate-950">Update pipeline status</h3>
                    <form method="POST" action="{{ route('employer.applications.status', $application) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="field-label">Status</label>
                            <select id="status" name="status" class="field-control">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($application->status->value === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full">Save status</button>
                    </form>
                </section>

                <section class="surface p-5">
                    <h3 class="text-base font-semibold text-slate-950">Message</h3>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $application->message ?: 'No message included.' }}</p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
