<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('AI CV Import') }}</h2>
                <p class="mt-1 text-sm text-gray-600">Upload a PDF or DOCX CV and review the extracted profile before saving.</p>
            </div>
            <a href="{{ route('candidate.profile.edit') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Back to profile</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section
                class="grid gap-6 bg-white p-6 shadow-sm sm:rounded-lg lg:grid-cols-[1fr_20rem]"
                x-data="{
                    analyzing: false,
                    fileName: '',
                    steps: ['Reading CV text', 'Extracting profile sections', 'Scoring CV appeal', 'Preparing review screen'],
                    activeStep: 0,
                    start() {
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
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Extract profile from CV</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        We will read the CV text, identify experience, education, skills, links and preferences, then show everything for confirmation. Nothing is saved until you approve it.
                    </p>

                    <form method="POST" action="{{ route('candidate.profile.ai.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-5" @submit="start()">
                        @csrf

                        <div>
                            <label for="cv" class="block text-sm font-medium text-gray-700">CV file</label>
                            <input
                                id="cv"
                                name="cv"
                                type="file"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="mt-2 block w-full rounded-md border border-dashed border-gray-300 p-3 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
                                @change="fileName = $event.target.files[0]?.name || ''"
                                :disabled="analyzing"
                            >
                            <p class="mt-2 text-xs text-gray-500">PDF or DOCX, maximum 5 MB.</p>
                            <p class="mt-2 text-sm font-medium text-gray-700" x-show="fileName" x-text="fileName" x-cloak></p>
                            <x-input-error class="mt-2" :messages="$errors->get('cv')" />
                        </div>

                        <button type="submit" class="btn-primary min-w-36" :disabled="analyzing" :class="analyzing ? 'cursor-not-allowed opacity-70' : ''">
                            <span x-show="!analyzing">Analyze CV</span>
                            <span x-show="analyzing" x-cloak>Analyzing...</span>
                        </button>
                    </form>
                </div>

                <aside class="rounded-lg border border-indigo-100 bg-indigo-50 p-5">
                    <p class="text-sm font-semibold text-indigo-950">AI analysis status</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700" x-show="!analyzing">
                        After upload, you will see live progress here while the CV is processed.
                    </p>

                    <div x-show="analyzing" x-cloak>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-2 rounded-full bg-indigo-600 transition-all duration-700" :style="`width: ${Math.min(96, 18 + (activeStep * 24))}%`"></div>
                        </div>

                        <ol class="mt-5 space-y-3">
                            <template x-for="(step, index) in steps" :key="step">
                                <li class="flex items-center gap-3 text-sm">
                                    <span
                                        class="grid h-6 w-6 place-items-center rounded-full text-xs font-bold"
                                        :class="index <= activeStep ? 'bg-indigo-700 text-white' : 'bg-white text-slate-500'"
                                    >
                                        <span x-show="index < activeStep">✓</span>
                                        <span x-show="index >= activeStep" x-text="index + 1"></span>
                                    </span>
                                    <span class="font-medium" :class="index <= activeStep ? 'text-slate-950' : 'text-slate-500'" x-text="step"></span>
                                </li>
                            </template>
                        </ol>

                        <div class="mt-5 rounded-md border border-indigo-200 bg-white p-3">
                            <p class="text-sm font-semibold text-slate-900">Please keep this page open.</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">CV analysis usually takes 15-45 seconds, depending on file size and OpenAI response time.</p>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
