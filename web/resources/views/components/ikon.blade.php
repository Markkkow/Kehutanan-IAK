@props(['nama'])

{{-- Ikon garis sederhana (SVG inline). Warna mengikuti currentColor. --}}
<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($nama)
        @case('daun')     {{-- tutupan hutan --}}
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10Z"/><path d="M2 21c0-3 1.9-5.4 5.1-6C9.5 14.5 12 13 13 12"/>
            @break
        @case('perisai')  {{-- kawasan hutan (dilindungi hukum) --}}
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m12 7-3 5h2l-2 3h6l-2-3h2Z"/>
            @break
        @case('api')      {{-- karhutla --}}
            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4.1 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.2.4-2.3 1-3a2.5 2.5 0 0 0 2.5 2.5Z"/>
            @break
        @case('kayu')     {{-- produksi kayu bulat --}}
            <path d="M17 7H6a5 5 0 0 0 0 10h11"/><ellipse cx="17" cy="12" rx="4" ry="5"/><path d="M17 10.5v3"/>
            @break
        @case('kapak')    {{-- deforestasi --}}
            <path d="m14 12-8.5 8.5a2.1 2.1 0 1 1-3-3L11 9"/><path d="M15 13 9 7l4-4 6 6h3a8 8 0 0 1-7 7Z"/>
            @break
        @case('peta')
            <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15M15 6v15"/>
            @break
        @case('lapisan')
            <path d="m12 2 10 5-10 5L2 7Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>
            @break
        @case('basisdata')
            <ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>
            @break
        @case('ringkasan')
            <rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>
            @break
        @case('grafik')
            <path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>
            @break
        @case('pohon')
            <path d="M12 2 6 10h3l-4 6h14l-4-6h3Z"/><path d="M12 16v6"/>
            @break
        @case('akun')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('bulan')
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            @break
        @case('matahari')
            <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
            @break
    @endswitch
</svg>
