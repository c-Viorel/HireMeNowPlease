<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Candidate Profile') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.profile.update') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg">
                @csrf

                <div class="space-y-6 p-6">
                    @if (session('status') === 'candidate-profile-updated')
                        <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Profile updated.</p>
                    @endif

                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $profile?->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="location" :value="__('Location')" />
                        <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $profile?->location)" />
                        <x-input-error class="mt-2" :messages="$errors->get('location')" />
                    </div>

                    <div>
                        <x-input-label for="headline" :value="__('Headline')" />
                        <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full" :value="old('headline', $profile?->headline)" />
                        <x-input-error class="mt-2" :messages="$errors->get('headline')" />
                    </div>

                    <div>
                        <x-input-label for="summary" :value="__('Summary')" />
                        <textarea id="summary" name="summary" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('summary', $profile?->summary) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('summary')" />
                    </div>

                    <div>
                        <x-input-label for="skills" :value="__('Skills')" />
                        <x-text-input id="skills" name="skills" type="text" class="mt-1 block w-full" :value="old('skills', implode(', ', $profile?->skills ?? []))" />
                        <x-input-error class="mt-2" :messages="$errors->get('skills')" />
                    </div>

                    <div>
                        <x-input-label for="cv" :value="__('CV')" />
                        <input id="cv" name="cv" type="file" class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700" />
                        <x-input-error class="mt-2" :messages="$errors->get('cv')" />

                        @if ($profile?->cv_path)
                            <p class="mt-3 text-sm text-gray-600">
                                Current CV:
                                <a href="{{ Illuminate\Support\Facades\Storage::url($profile->cv_path) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ basename($profile->cv_path) }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <a href="{{ route('candidate.dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ __('Save profile') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
