@props(['judul', 'sub' => null])

<div {{ $attributes->merge(['class' => 'mb-3 flex flex-wrap items-end justify-between gap-3 border-b border-garis pb-2.5']) }}>
    <div class="max-w-3xl">
        <h1 class="text-lg font-semibold text-balance md:text-xl">{{ $judul }}</h1>
        @if ($sub)
            <p class="mt-0.5 text-[12.5px] leading-snug text-pretty text-ink-2">{{ $sub }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
