@props(['kode' => null])

{{-- Dekorasi latar sudut kartu sesuai identitas dataset (D1–D5) dan analisis gabungan (G2, G3). Murni visual. --}}
@if ($kode)
<svg {{ $attributes->merge(['class' => 'dekor-ds pointer-events-none select-none']) }} viewBox="0 0 120 120" fill="none" aria-hidden="true">
    @switch($kode)
        @case('D1')
        @case('primer')
            {{-- Kanopi hutan primer berlapis --}}
            <path d="M88 18 L62 56 H75 L54 88 H122 L101 56 H114 Z" fill="currentColor" opacity="0.5"/>
            <path d="M58 38 L38 68 H48 L32 94 H84 L68 68 H78 Z" fill="currentColor" opacity="0.32"/>
            <path d="M88 88 V108 M58 94 V108" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity="0.4"/>
            @break
        @case('D2')
        @case('tutupan')
            {{-- Tulang daun & hamparan tutupan hijau --}}
            <path d="M108 12 C62 16 36 46 34 92 C80 90 106 60 108 12 Z" fill="currentColor" opacity="0.35"/>
            <path d="M34 92 C58 68 82 42 108 12" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.55"/>
            <path d="M54 72 L78 70 M66 58 L92 54 M78 44 L98 40" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" opacity="0.45"/>
            @break
        @case('D3')
        @case('karhutla')
            {{-- Lidah api & percikan bara karhutla --}}
            <path d="M78 14 C82 36 106 46 106 74 C106 94 90 108 70 108 C50 108 36 92 36 74 C36 58 46 46 54 38 C52 50 60 56 66 50 C62 34 70 22 78 14 Z" fill="currentColor" opacity="0.38"/>
            <path d="M72 48 C76 58 88 64 88 78 C88 90 80 98 70 98 C60 98 52 90 52 78 C52 68 62 58 72 48 Z" fill="currentColor" opacity="0.45"/>
            <circle cx="44" cy="28" r="3.5" fill="currentColor" opacity="0.5"/>
            <circle cx="98" cy="24" r="2.5" fill="currentColor" opacity="0.45"/>
            <circle cx="30" cy="48" r="2.5" fill="currentColor" opacity="0.35"/>
            @break
        @case('D4')
        @case('kayu')
            {{-- Penampang lingkaran tahun batang kayu bulat --}}
            <circle cx="82" cy="64" r="40" stroke="currentColor" stroke-width="4" opacity="0.42"/>
            <circle cx="82" cy="64" r="28" stroke="currentColor" stroke-width="2.5" stroke-dasharray="18 6" opacity="0.38"/>
            <circle cx="82" cy="64" r="16" stroke="currentColor" stroke-width="2.5" opacity="0.42"/>
            <circle cx="82" cy="64" r="5" fill="currentColor" opacity="0.5"/>
            <path d="M82 64 L106 42 M82 64 L62 90" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.35"/>
            @break
        @case('D5')
        @case('deforestasi')
            {{-- Tunggul pohon & garis tebangan deforestasi --}}
            <path d="M46 56 L98 32" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="6 5" opacity="0.5"/>
            <ellipse cx="76" cy="66" rx="28" ry="10" fill="currentColor" opacity="0.35"/>
            <path d="M48 66 V92 C48 98 40 104 34 106 H118 C112 104 104 98 104 92 V66" fill="currentColor" opacity="0.32"/>
            <path d="M64 44 L88 20 L102 34 L78 58 Z" fill="currentColor" opacity="0.28"/>
            @break
        @case('G2')
            {{-- Gabungan D3 (Api) x D5 (Deforestasi) --}}
            <path d="M62 18 C66 36 86 44 86 68 C86 84 74 96 58 96 C42 96 30 84 30 68 C30 52 44 38 62 18 Z" fill="currentColor" opacity="0.28"/>
            <path d="M24 84 L108 36" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="7 5" opacity="0.45"/>
            <ellipse cx="86" cy="86" rx="22" ry="8" fill="currentColor" opacity="0.32"/>
            @break
        @case('G3')
            {{-- Sintesis gelembung provinsi & jaringan korelasi (D3 x D4 x D5) --}}
            <line x1="38" y1="78" x2="78" y2="38" stroke="currentColor" stroke-width="2.5" stroke-dasharray="4 4" opacity="0.4"/>
            <line x1="78" y1="38" x2="98" y2="74" stroke="currentColor" stroke-width="2" opacity="0.3"/>
            <circle cx="78" cy="38" r="18" fill="currentColor" opacity="0.32"/>
            <circle cx="38" cy="78" r="12" fill="currentColor" opacity="0.28"/>
            <circle cx="98" cy="74" r="10" fill="currentColor" opacity="0.38"/>
            <circle cx="56" cy="48" r="6" fill="currentColor" opacity="0.25"/>
            @break
    @endswitch
</svg>
@endif
