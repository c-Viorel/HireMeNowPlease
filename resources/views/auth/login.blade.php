<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 grid gap-3">
        @foreach ([
            ['label' => 'Cont candidat demo', 'email' => 'candidate@hireme.local', 'description' => 'Exploreaza joburi, aplica si urmareste candidaturile.'],
            ['label' => 'Cont HR demo', 'email' => 'hr@hireme.local', 'description' => 'Publica roluri, vezi candidaturi si gestioneaza companii.'],
        ] as $demoAccount)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">{{ $demoAccount['label'] }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ $demoAccount['description'] }}</p>
                    </div>
                    <span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Demo</span>
                </div>
                <dl class="mt-3 grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-md bg-white px-3 py-2">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-slate-900">{{ $demoAccount['email'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-md bg-white px-3 py-2">
                        <dt class="text-slate-500">Parola</dt>
                        <dd class="font-medium text-slate-900">demo1234</dd>
                    </div>
                </dl>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
