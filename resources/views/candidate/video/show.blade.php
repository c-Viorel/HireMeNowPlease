<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Interviu video asincron</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Raspuns salvat.</div>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-950">Interviu video</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Raspunde la intrebari inregistrand un scurt clip video. Le poti completa cand ai timp.
                </p>

                <div class="mt-5 space-y-5">
                    @foreach ($interview->answers as $index => $answer)
                        <div class="rounded-md border border-slate-200 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $index + 1 }}. {{ $answer->question }}</p>

                            @if ($answer->video_path)
                                <p class="mt-2 text-xs font-medium text-emerald-700">Inregistrare trimisa.</p>
                            @else
                                <form method="POST"
                                      action="{{ route('candidate.video.answer', [$interview, $answer]) }}"
                                      enctype="multipart/form-data"
                                      class="mt-3 flex items-center gap-3">
                                    @csrf
                                    <input type="file" name="recording" accept="video/webm,video/mp4" class="text-sm">
                                    <button type="submit" class="btn-secondary">Trimite</button>
                                </form>
                                @error('recording')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
