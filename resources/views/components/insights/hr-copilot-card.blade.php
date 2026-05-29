@props(['brief' => null])

@if ($brief)
    <section {{ $attributes->merge(['class' => 'rounded-lg border border-emerald-200 bg-emerald-50 p-5']) }}>
        <p class="text-sm font-semibold text-emerald-950">HR Copilot</p>
        <h3 class="mt-1 text-lg font-bold text-slate-950">{{ $brief['title'] }}</h3>
        <p class="mt-2 text-sm text-slate-700">{{ $brief['summary'] }}</p>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-sm font-semibold text-slate-900">Motive de interes</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-700">
                    @foreach ($brief['strengths'] ?? [] as $strength)
                        <li>{{ $strength }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-900">Riscuri de clarificat</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-700">
                    @foreach ($brief['concerns'] ?? [] as $concern)
                        <li>{{ $concern }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-4 rounded-md bg-white p-4">
            <p class="text-sm font-semibold text-slate-900">Intrebari recomandate</p>
            <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-slate-700">
                @foreach ($brief['questions'] ?? [] as $question)
                    <li>{{ $question }}</li>
                @endforeach
            </ol>
        </div>

        <p class="mt-4 text-sm font-semibold text-emerald-950">Urmatorul pas: {{ $brief['next_action'] }}</p>
    </section>
@endif
