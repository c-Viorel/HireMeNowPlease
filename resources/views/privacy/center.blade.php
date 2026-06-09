<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Confidentialitate si date (GDPR)</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-950">Exporta-ti datele</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Descarca un fisier JSON cu toate datele personale pe care le detinem despre tine (profil, aplicari).
                </p>
                <a href="{{ route('privacy.export') }}" class="btn-secondary mt-4 inline-flex">Descarca datele mele</a>
            </section>

            <section class="rounded-lg border border-red-200 bg-red-50 p-6">
                <h3 class="text-lg font-semibold text-red-900">Sterge contul (dreptul de a fi uitat)</h3>
                <p class="mt-2 text-sm text-red-800">
                    Datele tale personale vor fi anonimizate ireversibil. Inregistrarile de angajare anonimizate pot fi
                    pastrate de angajatori in scop statistic, conform legii.
                </p>
                <form method="POST" action="{{ route('privacy.destroy') }}" class="mt-4" onsubmit="return confirm('Esti sigur? Aceasta actiune este ireversibila.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700">Sterge-mi contul</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
