<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Onboarding angajat</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Actualizat.</div>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-950">Checklist conformitate (ITM / REGES)</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Pasii legali pentru {{ $application->candidate->name }} — {{ $application->job->title }}.
                    Perioada de proba pana la {{ optional($checklist->probation_end_date)->format('d.m.Y') }}.
                </p>

                <ul class="mt-4 space-y-2">
                    @foreach ($checklist->tasks as $task)
                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-4 py-3">
                            <span class="text-sm {{ $task->is_done ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $task->label }}</span>
                            <form method="POST" action="{{ route('employer.applications.onboarding.task', [$application, $task]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                                    {{ $task->is_done ? 'Anuleaza' : 'Marcheaza facut' }}
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-950">Proiect Contract Individual de Munca</h3>
                <pre class="mt-3 whitespace-pre-wrap rounded-md bg-slate-50 p-4 text-sm text-slate-700">{{ $cimDraft }}</pre>
            </section>
        </div>
    </div>
</x-app-layout>
