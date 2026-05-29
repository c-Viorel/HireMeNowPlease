<x-app-layout>
    @php
        $blankExperience = [
            'title' => '',
            'company' => '',
            'employment_type' => '',
            'location' => '',
            'workplace_type' => '',
            'start_date' => '',
            'end_date' => '',
            'is_current' => false,
            'description' => '',
            'skills' => '',
        ];
        $blankEducation = [
            'institution' => '',
            'degree' => '',
            'field_of_study' => '',
            'start_date' => '',
            'end_date' => '',
            'is_current' => false,
            'description' => '',
        ];
        $blankCertification = [
            'name' => '',
            'issuer' => '',
            'issued_at' => '',
            'expires_at' => '',
            'credential_url' => '',
        ];
        $blankLink = ['label' => '', 'url' => ''];

        $experienceRows = old('experiences', $profile?->experiences?->map(fn ($experience) => [
            'title' => $experience->title,
            'company' => $experience->company,
            'employment_type' => $experience->employment_type,
            'location' => $experience->location,
            'workplace_type' => $experience->workplace_type,
            'start_date' => $experience->start_date?->toDateString(),
            'end_date' => $experience->end_date?->toDateString(),
            'is_current' => $experience->is_current,
            'description' => $experience->description,
            'skills' => implode(', ', $experience->skills ?? []),
        ])->values()->all() ?: [$blankExperience]);

        $educationRows = old('educations', $profile?->educations?->map(fn ($education) => [
            'institution' => $education->institution,
            'degree' => $education->degree,
            'field_of_study' => $education->field_of_study,
            'start_date' => $education->start_date?->toDateString(),
            'end_date' => $education->end_date?->toDateString(),
            'is_current' => $education->is_current,
            'description' => $education->description,
        ])->values()->all() ?: [$blankEducation]);

        $certificationRows = old('certifications', $profile?->certifications?->map(fn ($certification) => [
            'name' => $certification->name,
            'issuer' => $certification->issuer,
            'issued_at' => $certification->issued_at?->toDateString(),
            'expires_at' => $certification->expires_at?->toDateString(),
            'credential_url' => $certification->credential_url,
        ])->values()->all() ?: [$blankCertification]);

        $linkRows = old('links', $profile?->links?->map(fn ($link) => [
            'label' => $link->label,
            'url' => $link->url,
        ])->values()->all() ?: [$blankLink]);

        $preference = $profile?->jobPreference;
        $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-600 focus:ring-indigo-600';
        $labelClass = 'block text-sm font-medium text-gray-700';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Candidate Profile') }}</h2>
            <p class="text-sm text-gray-600">Build a profile recruiters can actually evaluate without opening a CV first.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('candidate.profile.update') }}"
                enctype="multipart/form-data"
                x-data="{
                    experiences: @js(array_values($experienceRows ?: [$blankExperience])),
                    educations: @js(array_values($educationRows ?: [$blankEducation])),
                    certifications: @js(array_values($certificationRows ?: [$blankCertification])),
                    links: @js(array_values($linkRows ?: [$blankLink])),
                    blankExperience: @js($blankExperience),
                    blankEducation: @js($blankEducation),
                    blankCertification: @js($blankCertification),
                    blankLink: @js($blankLink),
                    add(collection, blank) { this[collection].push(JSON.parse(JSON.stringify(this[blank]))); },
                    remove(collection, index, blank) { this[collection].splice(index, 1); if (this[collection].length === 0) this.add(collection, blank); }
                }"
                class="space-y-6"
            >
                @csrf

                @if (session('status') === 'candidate-profile-updated')
                    <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Profile updated.</p>
                @endif

                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
                        <div class="space-y-5">
                            <div>
                                <label for="headline" class="{{ $labelClass }}">Headline</label>
                                <input id="headline" name="headline" type="text" class="{{ $fieldClass }}" value="{{ old('headline', $profile?->headline) }}" placeholder="Senior Laravel Engineer | Marketplace platforms">
                                <x-input-error class="mt-2" :messages="$errors->get('headline')" />
                            </div>

                            <div>
                                <label for="summary" class="{{ $labelClass }}">About</label>
                                <textarea id="summary" name="summary" rows="6" class="{{ $fieldClass }}" placeholder="Write a concise professional summary with strengths, domains and impact.">{{ old('summary', $profile?->summary) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('summary')" />
                            </div>

                            <div>
                                <label for="skills" class="{{ $labelClass }}">Core skills</label>
                                <input id="skills" name="skills" type="text" class="{{ $fieldClass }}" value="{{ old('skills', implode(', ', $profile?->skills ?? [])) }}" placeholder="Laravel, MySQL, Product discovery, Playwright">
                                <x-input-error class="mt-2" :messages="$errors->get('skills')" />
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label for="phone" class="{{ $labelClass }}">Phone</label>
                                <input id="phone" name="phone" type="text" class="{{ $fieldClass }}" value="{{ old('phone', $profile?->phone) }}">
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>

                            <div>
                                <label for="location" class="{{ $labelClass }}">Location</label>
                                <input id="location" name="location" type="text" class="{{ $fieldClass }}" value="{{ old('location', $profile?->location) }}">
                                <x-input-error class="mt-2" :messages="$errors->get('location')" />
                            </div>

                            <div>
                                <label for="cv" class="{{ $labelClass }}">CV</label>
                                <input id="cv" name="cv" type="file" class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700">
                                <x-input-error class="mt-2" :messages="$errors->get('cv')" />
                                @if ($profile?->cv_path)
                                    <p class="mt-3 text-sm text-gray-600">
                                        Current CV:
                                        <a href="{{ route('candidate.profile.cv') }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ basename($profile->cv_path) }}</a>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Experience</h3>
                            <p class="mt-1 text-sm text-gray-600">Add roles with dates, work mode, responsibilities and role-specific skills.</p>
                        </div>
                        <button type="button" class="btn-secondary" @click="add('experiences', 'blankExperience')">Add role</button>
                    </div>

                    <div class="mt-6 space-y-5">
                        <template x-for="(experience, index) in experiences" :key="index">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <h4 class="font-semibold text-gray-900">Role <span x-text="index + 1"></span></h4>
                                    <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700" @click="remove('experiences', index, 'blankExperience')">Remove</button>
                                </div>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="{{ $labelClass }}">Title</label>
                                        <input type="text" class="{{ $fieldClass }}" x-model="experience.title" :name="`experiences[${index}][title]`">
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Company</label>
                                        <input type="text" class="{{ $fieldClass }}" x-model="experience.company" :name="`experiences[${index}][company]`">
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Employment type</label>
                                        <select class="{{ $fieldClass }}" x-model="experience.employment_type" :name="`experiences[${index}][employment_type]`">
                                            <option value="">Select</option>
                                            <option value="full_time">Full-time</option>
                                            <option value="part_time">Part-time</option>
                                            <option value="contract">Contract</option>
                                            <option value="internship">Internship</option>
                                            <option value="freelance">Freelance</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Workplace</label>
                                        <select class="{{ $fieldClass }}" x-model="experience.workplace_type" :name="`experiences[${index}][workplace_type]`">
                                            <option value="">Select</option>
                                            <option value="remote">Remote</option>
                                            <option value="hybrid">Hybrid</option>
                                            <option value="on_site">On-site</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Location</label>
                                        <input type="text" class="{{ $fieldClass }}" x-model="experience.location" :name="`experiences[${index}][location]`">
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="{{ $labelClass }}">Start date</label>
                                            <input type="date" class="{{ $fieldClass }}" x-model="experience.start_date" :name="`experiences[${index}][start_date]`">
                                        </div>
                                        <div>
                                            <label class="{{ $labelClass }}">End date</label>
                                            <input type="date" class="{{ $fieldClass }}" x-model="experience.end_date" :name="`experiences[${index}][end_date]`" :disabled="experience.is_current">
                                        </div>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                        <input type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-600" x-model="experience.is_current" :name="`experiences[${index}][is_current]`">
                                        I currently work here
                                    </label>
                                    <div>
                                        <label class="{{ $labelClass }}">Skills used</label>
                                        <input type="text" class="{{ $fieldClass }}" x-model="experience.skills" :name="`experiences[${index}][skills]`" placeholder="Laravel, Redis, Stakeholder management">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="{{ $labelClass }}">Description</label>
                                        <textarea rows="4" class="{{ $fieldClass }}" x-model="experience.description" :name="`experiences[${index}][description]`"></textarea>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <x-input-error class="mt-4" :messages="$errors->get('experiences.0.end_date')" />
                </section>

                <section class="grid gap-6 lg:grid-cols-2">
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-gray-900">Education</h3>
                            <button type="button" class="btn-secondary" @click="add('educations', 'blankEducation')">Add education</button>
                        </div>
                        <div class="mt-5 space-y-4">
                            <template x-for="(education, index) in educations" :key="index">
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <h4 class="font-semibold text-gray-900">Education <span x-text="index + 1"></span></h4>
                                        <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700" @click="remove('educations', index, 'blankEducation')">Remove</button>
                                    </div>
                                    <div class="mt-4 grid gap-4">
                                        <input type="text" class="{{ $fieldClass }}" x-model="education.institution" :name="`educations[${index}][institution]`" placeholder="Institution">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <input type="text" class="{{ $fieldClass }}" x-model="education.degree" :name="`educations[${index}][degree]`" placeholder="Degree">
                                            <input type="text" class="{{ $fieldClass }}" x-model="education.field_of_study" :name="`educations[${index}][field_of_study]`" placeholder="Field of study">
                                            <input type="date" class="{{ $fieldClass }}" x-model="education.start_date" :name="`educations[${index}][start_date]`">
                                            <input type="date" class="{{ $fieldClass }}" x-model="education.end_date" :name="`educations[${index}][end_date]`" :disabled="education.is_current">
                                        </div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                            <input type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-600" x-model="education.is_current" :name="`educations[${index}][is_current]`">
                                            In progress
                                        </label>
                                        <textarea rows="3" class="{{ $fieldClass }}" x-model="education.description" :name="`educations[${index}][description]`" placeholder="Activities, achievements, thesis or relevant coursework"></textarea>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <x-input-error class="mt-4" :messages="$errors->get('educations.0.end_date')" />
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-semibold text-gray-900">Certifications</h3>
                                <button type="button" class="btn-secondary" @click="add('certifications', 'blankCertification')">Add certification</button>
                            </div>
                            <div class="mt-5 space-y-4">
                                <template x-for="(certification, index) in certifications" :key="index">
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="flex justify-end">
                                            <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700" @click="remove('certifications', index, 'blankCertification')">Remove</button>
                                        </div>
                                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                            <input type="text" class="{{ $fieldClass }}" x-model="certification.name" :name="`certifications[${index}][name]`" placeholder="Certification">
                                            <input type="text" class="{{ $fieldClass }}" x-model="certification.issuer" :name="`certifications[${index}][issuer]`" placeholder="Issuer">
                                            <input type="date" class="{{ $fieldClass }}" x-model="certification.issued_at" :name="`certifications[${index}][issued_at]`">
                                            <input type="date" class="{{ $fieldClass }}" x-model="certification.expires_at" :name="`certifications[${index}][expires_at]`">
                                            <input type="url" class="{{ $fieldClass }} sm:col-span-2" x-model="certification.credential_url" :name="`certifications[${index}][credential_url]`" placeholder="Credential URL">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-semibold text-gray-900">Links</h3>
                                <button type="button" class="btn-secondary" @click="add('links', 'blankLink')">Add link</button>
                            </div>
                            <div class="mt-5 space-y-4">
                                <template x-for="(link, index) in links" :key="index">
                                    <div class="grid gap-3 rounded-lg border border-gray-200 p-4 sm:grid-cols-[10rem_1fr_auto]">
                                        <input type="text" class="{{ $fieldClass }}" x-model="link.label" :name="`links[${index}][label]`" placeholder="LinkedIn">
                                        <input type="url" class="{{ $fieldClass }}" x-model="link.url" :name="`links[${index}][url]`" placeholder="https://...">
                                        <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700" @click="remove('links', index, 'blankLink')">Remove</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Job preferences</h3>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="availability" class="{{ $labelClass }}">Availability</label>
                            <input id="availability" name="availability" type="text" class="{{ $fieldClass }}" value="{{ old('availability', $preference?->availability) }}" placeholder="Immediate, 30 days, 60 days">
                        </div>
                        <div>
                            <label for="experience_level" class="{{ $labelClass }}">Experience level</label>
                            <input id="experience_level" name="experience_level" type="text" class="{{ $fieldClass }}" value="{{ old('experience_level', $preference?->experience_level) }}" placeholder="junior, mid, senior, lead">
                        </div>
                        <div>
                            <label for="desired_salary_min" class="{{ $labelClass }}">Desired salary min</label>
                            <input id="desired_salary_min" name="desired_salary_min" type="number" min="0" class="{{ $fieldClass }}" value="{{ old('desired_salary_min', $preference?->desired_salary_min) }}">
                        </div>
                        <div>
                            <label for="desired_salary_max" class="{{ $labelClass }}">Desired salary max</label>
                            <input id="desired_salary_max" name="desired_salary_max" type="number" min="0" class="{{ $fieldClass }}" value="{{ old('desired_salary_max', $preference?->desired_salary_max) }}">
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">Workplace preferences</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-sm text-gray-700">
                                @foreach (['remote' => 'Remote', 'hybrid' => 'Hybrid', 'on_site' => 'On-site'] as $value => $label)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="preferred_workplace_types[]" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-600" @checked(in_array($value, old('preferred_workplace_types', $preference?->preferred_workplace_types ?? []), true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">Contract preferences</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-sm text-gray-700">
                                @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship'] as $value => $label)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="preferred_employment_types[]" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-600" @checked(in_array($value, old('preferred_employment_types', $preference?->preferred_employment_types ?? []), true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('candidate.dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ __('Save profile') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
