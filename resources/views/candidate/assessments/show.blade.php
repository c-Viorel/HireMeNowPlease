<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $assessment->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.assessments.submit', $assessment) }}" class="space-y-6">
                @csrf

                @foreach ($assessment->questions as $index => $question)
                    <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                        <legend class="text-sm font-semibold text-slate-900">{{ $index + 1 }}. {{ $question->prompt }}</legend>
                        <div class="mt-3 space-y-2">
                            @foreach ($question->choices as $choiceIndex => $choice)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="answers[{{ $index }}]" value="{{ $choiceIndex }}" class="border-slate-300 text-emerald-600 focus:ring-emerald-600" required>
                                    {{ $choice }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <button type="submit" class="btn-primary">Trimite raspunsurile</button>
            </form>
        </div>
    </div>
</x-app-layout>
