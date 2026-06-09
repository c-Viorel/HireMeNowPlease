<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">AI profile assistant</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ __('AI CV Import') }}</h2>
                <p class="mt-1 text-sm text-slate-600">Upload a PDF or DOCX CV and review the extracted profile before saving.</p>
            </div>
            <a href="{{ route('candidate.profile.edit') }}" class="btn-secondary">Back to profile</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section
                class="surface overflow-hidden"
                x-data="{
                    analyzing: false,
                    fileName: '',
                    steps: ['Reading CV text', 'Extracting profile sections', 'Scoring CV appeal', 'Preparing review screen'],
                    activeStep: 0,
                    start() {
                        if (this.analyzing) return;
                        this.analyzing = true;
                        this.activeStep = 0;
                        const timer = setInterval(() => {
                            if (!this.analyzing || this.activeStep >= this.steps.length - 1) {
                                clearInterval(timer);
                                return;
                            }
                            this.activeStep++;
                        }, 2600);
                    }
                }"
            >
                <div class="grid gap-0 lg:grid-cols-[1fr_22rem]">
                    <div class="p-6 sm:p-8">
                        <p class="section-eyebrow">Extract profile from CV</p>
                        <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Transforma CV-ul in profil structurat</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            We will read the CV text, identify experience, education, skills, links and preferences, then show everything for confirmation. Nothing is saved until you approve it.
                        </p>

                        <form method="POST" action="{{ route('candidate.profile.ai.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-5" @submit="start()">
                            @csrf

                            <div>
                                <label for="cv" class="field-label">CV file</label>
                                <label
                                    for="cv"
                                    class="mt-2 flex cursor-pointer flex-col gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 transition hover:border-emerald-400 hover:bg-emerald-50/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <span>
                                        <span class="block text-sm font-bold text-slate-950" x-text="fileName || 'Choose a PDF or DOCX file'"></span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">PDF or DOCX, maximum 5 MB. Analysis usually takes 15-45 seconds.</span>
                                    </span>
                                    <span class="btn-secondary pointer-events-none shrink-0">Choose File</span>
                                </label>
                                <input
                                    id="cv"
                                    name="cv"
                                    type="file"
                                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''"
                                >
                                <x-input-error class="mt-2" :messages="$errors->get('cv')" />
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <button type="submit" class="btn-primary min-w-36" :class="analyzing ? 'pointer-events-none cursor-wait opacity-70' : ''">
                                    <span x-show="!analyzing">Analyze CV</span>
                                    <span x-show="analyzing" x-cloak>Analyzing...</span>
                                </button>
                                <p class="text-sm text-slate-500" x-show="fileName && !analyzing" x-cloak>Fisier pregatit pentru analiza.</p>
                            </div>
                        </form>
                    </div>

                    <aside class="border-t border-slate-200 bg-slate-950 p-6 text-white lg:border-l lg:border-t-0">
                        <p class="text-sm font-semibold text-emerald-300">AI analysis status</p>
                        <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                            <div class="h-2 rounded-full bg-emerald-400 transition-all duration-700" :style="`width: ${analyzing ? Math.min(96, 18 + (activeStep * 24)) : 8}%`"></div>
                        </div>

                        <ol class="mt-5 space-y-3">
                            <template x-for="(step, index) in steps" :key="step">
                                <li class="flex items-center gap-3 text-sm">
                                    <span
                                        class="grid h-7 w-7 place-items-center rounded-full text-xs font-bold"
                                        :class="analyzing && index <= activeStep ? 'bg-emerald-400 text-slate-950' : 'bg-white/10 text-slate-300'"
                                    >
                                        <span x-show="analyzing && index < activeStep">✓</span>
                                        <span x-show="!analyzing || index >= activeStep" x-text="index + 1"></span>
                                    </span>
                                    <span :class="analyzing && index <= activeStep ? 'text-white' : 'text-slate-300'" x-text="step"></span>
                                </li>
                            </template>
                        </ol>

                        <div class="mt-6 rounded-lg border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold" x-text="analyzing ? 'Please keep this page open.' : 'Ce se intampla dupa upload?'"></p>
                            <p class="mt-2 text-xs leading-5 text-slate-300" x-show="!analyzing">
                                Vei primi un preview editabil cu experienta, educatie, skill-uri, linkuri si preferinte. Salvezi doar daca esti multumit.
                            </p>
                            <p class="mt-2 text-xs leading-5 text-slate-300" x-show="analyzing" x-cloak>
                                Procesam CV-ul si pregatim ecranul de confirmare. Daca raspunsul AI intarzie, progresul ramane aici ca feedback vizual.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
