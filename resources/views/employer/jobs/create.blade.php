<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create Job') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('employer.jobs.store') }}" class="bg-white shadow-sm sm:rounded-lg">
                @csrf

                <div class="space-y-6 p-6">
                    <div>
                        <x-input-label for="company_id" :value="__('Company')" />
                        <select id="company_id" name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Choose a company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
                    </div>

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="location" :value="__('Location')" />
                            <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location')" />
                            <x-input-error class="mt-2" :messages="$errors->get('location')" />
                        </div>

                        <div>
                            <x-input-label for="experience_level" :value="__('Experience level')" />
                            <x-text-input id="experience_level" name="experience_level" type="text" class="mt-1 block w-full" :value="old('experience_level')" />
                            <x-input-error class="mt-2" :messages="$errors->get('experience_level')" />
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="employment_type" :value="__('Employment type')" />
                            <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @foreach (['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract', 'internship' => 'Internship'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('employment_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('employment_type')" />
                        </div>

                        <div>
                            <x-input-label for="workplace_type" :value="__('Workplace type')" />
                            <select id="workplace_type" name="workplace_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @foreach (['remote' => 'Remote', 'hybrid' => 'Hybrid', 'on_site' => 'On site'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('workplace_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('workplace_type')" />
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-3">
                        <div>
                            <x-input-label for="salary_min" :value="__('Salary min')" />
                            <x-text-input id="salary_min" name="salary_min" type="number" min="0" class="mt-1 block w-full" :value="old('salary_min')" />
                            <x-input-error class="mt-2" :messages="$errors->get('salary_min')" />
                        </div>
                        <div>
                            <x-input-label for="salary_max" :value="__('Salary max')" />
                            <x-text-input id="salary_max" name="salary_max" type="number" min="0" class="mt-1 block w-full" :value="old('salary_max')" />
                            <x-input-error class="mt-2" :messages="$errors->get('salary_max')" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                                <option value="published" @selected(old('status') === 'published')>Published</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <a href="{{ route('employer.jobs.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ __('Save job') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
