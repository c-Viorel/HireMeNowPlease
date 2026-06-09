<x-public-layout>
    <x-slot name="title">{{ $company->name }} - {{ config('app.name', 'HireMe') }}</x-slot>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
        <article class="space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Inapoi la joburi</a>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ $company->name }}</h1>
                @if ($company->location)
                    <p class="mt-1 text-sm text-slate-500">{{ $company->location }}</p>
                @endif
                @if ($company->description)
                    <p class="mt-4 text-sm text-slate-700">{{ $company->description }}</p>
                @endif
            </div>

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-950">Evaluari verificate de la candidati</h2>
                <p class="mt-1 text-sm text-slate-500">Doar candidati care au aplicat efectiv pot lasa o evaluare.</p>

                @forelse ($reviews as $review)
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $review->candidate->name }}</p>
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                {{ $review->rating }}/5 · verificat
                            </span>
                        </div>
                        @if ($review->body)
                            <p class="mt-2 text-sm text-slate-700">{{ $review->body }}</p>
                        @endif
                        @if ($review->would_apply_again)
                            <p class="mt-1 text-xs text-emerald-700">Ar aplica din nou la aceasta companie</p>
                        @endif
                    </div>
                @empty
                    <p class="mt-4 text-sm text-slate-500">Inca nu exista evaluari verificate.</p>
                @endforelse
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-950">Joburi active</h2>
                @forelse ($jobs as $job)
                    <a href="{{ route('jobs.show', [$company, $job]) }}" class="mt-4 block rounded-md border border-slate-200 p-4 hover:border-emerald-400">
                        <p class="font-semibold text-slate-900">{{ $job->title }}</p>
                        <p class="text-sm text-slate-500">{{ $job->location ?: 'Locatie flexibila' }}</p>
                    </a>
                @empty
                    <p class="mt-4 text-sm text-slate-500">Nu exista joburi active momentan.</p>
                @endforelse
            </div>
        </article>

        <aside class="h-fit space-y-5">
            <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-6">
                <p class="text-sm font-semibold text-emerald-950">Scor de incredere angajator</p>
                <p class="mt-3 text-4xl font-bold text-emerald-900">{{ $trust['score'] }}<span class="text-lg font-medium text-emerald-700">/100</span></p>
                <p class="mt-1 text-sm font-semibold text-emerald-800">{{ $trust['label'] }}</p>

                <dl class="mt-4 space-y-2 text-sm text-emerald-900">
                    <div class="flex justify-between">
                        <dt>Rating mediu</dt>
                        <dd class="font-semibold">{{ $trust['average_rating'] !== null ? $trust['average_rating'].'/5' : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Evaluari verificate</dt>
                        <dd class="font-semibold">{{ $trust['review_count'] }}</dd>
                    </div>
                    @if ($trust['would_apply_again_rate'] !== null)
                        <div class="flex justify-between">
                            <dt>Ar aplica din nou</dt>
                            <dd class="font-semibold">{{ $trust['would_apply_again_rate'] }}%</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt>Rata de raspuns</dt>
                        <dd class="font-semibold">{{ $trust['responsiveness']['response_rate'] }}%</dd>
                    </div>
                    @if ($trust['responsiveness']['average_response_hours'] !== null)
                        <div class="flex justify-between">
                            <dt>Raspunde in</dt>
                            <dd class="font-semibold">~{{ $trust['responsiveness']['average_response_hours'] }}h</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @auth
                @if (auth()->user()->role === \App\Enums\UserRole::Candidate)
                    @php($reviewableApplication = auth()->user()->applications()->whereHas('job', fn ($q) => $q->where('company_id', $company->id))->first())
                    @if ($reviewableApplication)
                        <section class="rounded-lg border border-slate-200 bg-white p-6">
                            <h2 class="text-sm font-semibold text-slate-950">Lasa o evaluare verificata</h2>
                            <form method="POST" action="{{ route('companies.reviews.store', $company) }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="application_id" value="{{ $reviewableApplication->id }}">
                                <div>
                                    <label for="rating" class="text-sm font-medium text-slate-800">Rating</label>
                                    <select id="rating" name="rating" class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        @foreach (range(5, 1) as $value)
                                            <option value="{{ $value }}">{{ $value }} / 5</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="would_apply_again" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" checked>
                                    As aplica din nou
                                </label>
                                <div>
                                    <label for="body" class="text-sm font-medium text-slate-800">Experienta ta</label>
                                    <textarea id="body" name="body" rows="4" maxlength="2000" class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('body') }}</textarea>
                                </div>
                                <button type="submit" class="btn-primary w-full">Publica evaluarea</button>
                            </form>
                        </section>
                    @endif
                @endif
            @endauth
        </aside>
    </section>
</x-public-layout>
