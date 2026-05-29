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
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Extract profile from CV</h3>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    We will read the CV text, identify experience, education, skills, links and preferences, then show everything for confirmation. Nothing is saved until you approve it.
                </p>

                <form method="POST" action="{{ route('candidate.profile.ai.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="cv" class="block text-sm font-medium text-gray-700">CV file</label>
                        <input id="cv" name="cv" type="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700">
                        <p class="mt-2 text-xs text-gray-500">PDF or DOCX, maximum 5 MB.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('cv')" />
                    </div>

                    <button type="submit" class="btn-primary">Analyze CV</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
