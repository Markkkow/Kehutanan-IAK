{{-- Ilustrasi lanskap hutan (SVG inline): bukit berlapis dan deretan pohon pinus. Murni dekorasi. --}}
@php
    // Posisi pohon dibuat deterministik (bukan acak) supaya gambarnya selalu sama
    $baris = [
        ['y' => 210, 'tinggi' => 46, 'jarak' => 34, 'warna' => '#3f6f4a', 'geser' => 0],
        ['y' => 245, 'tinggi' => 62, 'jarak' => 42, 'warna' => '#2c5537', 'geser' => 17],
        ['y' => 288, 'tinggi' => 84, 'jarak' => 54, 'warna' => '#1c3b26', 'geser' => 8],
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'pointer-events-none absolute inset-x-0 bottom-0 h-full w-full']) }} viewBox="0 0 1200 300" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
    <circle cx="1010" cy="70" r="38" fill="#f3d9a4" opacity="0.55"/>
    <path d="M0 190 C140 150 240 170 360 150 C500 128 600 168 740 148 C880 128 980 160 1200 132 V300 H0Z" fill="#5d8a5f" opacity="0.55"/>
    <path d="M0 215 C160 190 300 205 430 188 C580 170 700 200 860 182 C1000 168 1100 190 1200 176 V300 H0Z" fill="#4a7a52" opacity="0.7"/>
    @foreach ($baris as $i => $b)
        @for ($x = -20 + $b['geser']; $x < 1230; $x += $b['jarak'])
            @php
                $t = $b['tinggi'] * (0.78 + 0.22 * abs(sin(($x + $i * 37) * 0.13)));
                $w = $t * 0.42;
            @endphp
            <path d="M{{ $x }} {{ $b['y'] - $t }} L{{ $x - $w / 2 }} {{ $b['y'] - $t * 0.35 }} L{{ $x - $w * 0.32 }} {{ $b['y'] - $t * 0.35 }} L{{ $x - $w * 0.62 }} {{ $b['y'] }} L{{ $x + $w * 0.62 }} {{ $b['y'] }} L{{ $x + $w * 0.32 }} {{ $b['y'] - $t * 0.35 }} L{{ $x + $w / 2 }} {{ $b['y'] - $t * 0.35 }} Z" fill="{{ $b['warna'] }}"/>
        @endfor
        <rect x="0" y="{{ $b['y'] - 2 }}" width="1200" height="{{ 300 - $b['y'] + 2 }}" fill="{{ $b['warna'] }}"/>
    @endforeach
</svg>
