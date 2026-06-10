<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Messages') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="surface flex h-[calc(100vh-12rem)] overflow-hidden p-0">
                @include('conversations.partials.sidebar')

                <section class="hidden flex-1 flex-col items-center justify-center bg-slate-50 p-8 text-center md:flex">
                    <div class="grid h-16 w-16 place-items-center rounded-full bg-white text-slate-300 shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h13A2.5 2.5 0 0 1 21 8.5v6a2.5 2.5 0 0 1-2.5 2.5H9l-4 3v-3H5.5A2.5 2.5 0 0 1 3 14.5v-6Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-700">Selecteaza o conversatie</p>
                    <p class="mt-1 text-sm text-slate-500">Alege un candidat sau angajator din lista pentru a vedea mesajele.</p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
