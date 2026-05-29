<x-app-layout>
    @php
        $analysis = $data['cv_analysis'] ?? [];
        $preference = $data['job_preference'] ?? [];
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Review AI CV Import') }}</h2>
                <p class="mt-1 text-sm text-gray-600">Confirm the extracted data before it updates your profile.</p>
            </div>
            <a href="{{ route('candidate.profile.ai.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Upload another CV</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-6 lg:grid-cols-[1fr_22rem]">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Extracted profile</p>
                    <h3 class="mt-2 text-2xl font-semibold text-gray-900">{{ $data['headline'] ?: 'No headline detected' }}</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-600">{{ $data['summary'] ?: 'No summary detected.' }}</p>

                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-600">{{ $data['phone'] ?: 'Not detected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Location</dt>
                            <dd class="mt-1 text-sm text-gray-600">{{ $data['location'] ?: 'Not detected' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        <p class="text-sm font-medium text-gray-900">Skills</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($data['skills'] ?? [] as $skill)
                                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $skill }}</span>
                            @empty
                                <span class="text-sm text-gray-600">No skills detected.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-violet-200 bg-violet-50 p-6">
                    <p class="text-sm font-semibold text-violet-950">CV appeal score</p>
                    <p class="mt-2 text-4xl font-bold text-slate-950">{{ $analysis['score'] ?? 0 }}%</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Strengths</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                @foreach ($analysis['strengths'] ?? [] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Improvements</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                @foreach ($analysis['improvements'] ?? [] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Experience</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($data['experiences'] ?? [] as $experience)
                            <article class="border-l-2 border-indigo-200 pl-4">
                                <p class="font-semibold text-gray-900">{{ $experience['title'] ?? 'Role' }}</p>
                                <p class="text-sm text-gray-600">{{ $experience['company'] ?? 'Company not detected' }}</p>
                                <p class="mt-2 text-sm text-gray-700">{{ $experience['description'] ?? '' }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-gray-600">No experience detected.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Education, credentials and preferences</h3>
                    <div class="mt-5 space-y-4 text-sm text-gray-700">
                        @foreach ($data['educations'] ?? [] as $education)
                            <p><span class="font-semibold text-gray-900">{{ $education['institution'] ?? 'Institution' }}</span> · {{ $education['degree'] ?? 'Degree' }}</p>
                        @endforeach
                        @foreach ($data['certifications'] ?? [] as $certification)
                            <p><span class="font-semibold text-gray-900">{{ $certification['name'] ?? 'Certification' }}</span> · {{ $certification['issuer'] ?? 'Issuer' }}</p>
                        @endforeach
                        @foreach ($data['links'] ?? [] as $link)
                            <p><span class="font-semibold text-gray-900">{{ $link['label'] ?? 'Link' }}</span> · {{ $link['url'] ?? '' }}</p>
                        @endforeach
                        <p><span class="font-semibold text-gray-900">Availability:</span> {{ $preference['availability'] ?? 'Not detected' }}</p>
                    </div>
                </div>
            </section>

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Rewrite suggestions</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    @foreach ($analysis['rewrite_suggestions'] ?? [] as $suggestion)
                        <li>{{ $suggestion }}</li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('candidate.profile.ai.apply') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <button type="submit" class="btn-primary">Save to my profile</button>
                    <a href="{{ route('candidate.profile.edit') }}" class="btn-secondary">Cancel</a>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
