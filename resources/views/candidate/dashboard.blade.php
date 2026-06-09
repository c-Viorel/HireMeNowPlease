<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Candidat</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">
                    {{ __('Candidate Dashboard') }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">Tot ce conteaza azi: profil, potriviri, aplicari si mesaje.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('candidate.profile.ai.create') }}" class="btn-primary">Importa CV</a>
                <a href="{{ route('candidate.profile.edit') }}" class="btn-secondary">Edit profile</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="action-strip p-5">
                <div class="grid gap-5 lg:grid-cols-[18rem_1fr] lg:items-center">
                    <div>
                        <p class="text-sm font-semibold text-emerald-950">Urmatoarea actiune buna</p>
                        <p class="mt-1 text-sm leading-6 text-emerald-900">Alege rapid ce muta profilul sau aplicatiile mai departe.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($nextActions as $action)
                            <a
                                href="{{ $action['href'] }}"
                                @class([
                                    'rounded-lg border p-4 transition hover:-translate-y-0.5 hover:shadow-sm',
                                    'border-emerald-300 bg-white text-emerald-950' => $action['tone'] === 'primary',
                                    'border-white/80 bg-white/70 text-slate-900' => $action['tone'] !== 'primary',
                                ])
                            >
                                <span class="text-sm font-bold">{{ $action['label'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $action['description'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="surface p-5">
                <div class="grid gap-5 lg:grid-cols-[1fr_13rem] lg:items-center">
                    <div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-950">Profile completion</h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Keep your candidate profile current so employers can evaluate you quickly.
                                </p>
                            </div>
                            <div class="text-4xl font-bold tracking-tight text-slate-950">{{ $profileCompletion }}%</div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $profileCompletion }}%"></div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Profil public</p>
                        <p class="mt-2 text-sm font-semibold text-slate-950">{{ $profile?->headline ?: 'Headline lipsa' }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ $profile?->location ?: 'Adauga locatia' }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                <x-insights.profile-coach-card :coach="$profileCoach" />

                <section class="surface p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="section-eyebrow">Fit explicabil</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Best matches for you</h3>
                            <p class="mt-1 text-sm text-slate-600">Roluri ordonate dupa potrivirea cu profilul tau structurat.</p>
                        </div>
                        <a href="{{ route('jobs.index') }}" class="btn-secondary shrink-0">Explore</a>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @forelse ($bestMatches as $match)
                            <article class="rounded-lg border border-slate-200 p-4 transition hover:border-emerald-300 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm text-slate-500">{{ $match['job']->company->name }}</p>
                                        <a href="{{ route('jobs.show', [$match['job']->company, $match['job']]) }}" class="mt-1 block font-semibold leading-snug text-slate-950 hover:text-emerald-700">
                                            {{ $match['job']->title }}
                                        </a>
                                    </div>
                                    <span class="chip-sky">{{ $match['fit']['score'] }}%</span>
                                </div>
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $match['fit']['recommendation'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach (array_slice($match['fit']['matched_skills'] ?? [], 0, 3) as $skill)
                                        <span class="chip-emerald">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-600 sm:col-span-2">Completeaza profilul ca sa primesti potriviri explicabile.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-950">Recent applications</h3>
                        <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Vezi toate</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recentApplications as $application)
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="font-semibold leading-snug text-slate-950">{{ $application->job->title }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $application->job->company->name }}</p>
                                    </div>
                                    <span class="chip">{{ ucfirst($application->status->value) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-600">No applications yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-950">Recent conversations</h3>
                        <a href="{{ route('conversations.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Mesaje</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recentConversations as $conversation)
                            <div class="px-5 py-4">
                                <p class="font-semibold leading-snug text-slate-950">
                                    Conversation about {{ $conversation->application->job->title }}
                                </p>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $conversation->application->job->company->name }}
                                </p>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-600">No conversations yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
