@props([
    'name' => '',
    'email' => null,
    'size' => 40,
])

@php
    $clean = trim((string) $name);
    $parts = preg_split('/\s+/', $clean) ?: [];
    $initials = collect($parts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : '?';

    // Deterministic background color from the name.
    $palette = ['#0ea5e9', '#10b981', '#6366f1', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#8b5cf6', '#f97316', '#22c55e'];
    $hash = crc32($clean !== '' ? $clean : 'guest');
    $bg = $palette[$hash % count($palette)];

    $px = (int) $size;
    $fontSize = max(11, (int) round($px * 0.4));
    $gravatar = $email
        ? 'https://www.gravatar.com/avatar/'.md5(strtolower(trim((string) $email))).'?d=404&s='.($px * 2)
        : null;
@endphp

<span
    {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold text-white']) }}
    style="width: {{ $px }}px; height: {{ $px }}px; background-color: {{ $bg }}; font-size: {{ $fontSize }}px;"
    title="{{ $clean }}"
>
    <span>{{ $initials }}</span>
    @if ($gravatar)
        <img
            src="{{ $gravatar }}"
            alt="{{ $clean }}"
            loading="lazy"
            class="absolute inset-0 h-full w-full rounded-full object-cover"
            onerror="this.style.display='none'"
        >
    @endif
</span>
