<x-judul-panel judul="Analisis per data"
               sub="Satu baris per data: kiri tren nasional, kanan sebaran per provinsi." />

@php
    // [kode, partial, label (hanya untuk pembaca layar), jenis pemandangan, gaya]
    $trkDaftar = [
        ['D1', 'kelas-lahan', 'Hutan primer dan kelas lahan', 'pohon', 'tumbuh'],
        ['D2', 'tutupan', 'Tutupan hutan', 'bumi', 'bumi'],
        ['D3', 'karhutla', 'Kebakaran hutan dan lahan', 'bara', 'bara'],
        ['D4', 'kayu', 'Produksi kayu bulat', 'lingkaran', 'cincin'],
        ['D5', 'deforestasi', 'Deforestasi netto', 'pohon', 'hilang'],
    ];
@endphp

@foreach ($trkDaftar as [$trkKode, $trkPartial, $trkLabel, $trkJenis, $trkGaya])
    <section class="trek" id="slide-{{ strtolower($trkKode) }}" data-trek="{{ $loop->index }}" data-ds="{{ $trkKode }}" aria-label="{{ $trkLabel }}">
        <div class="panggung">
            <div class="latar-adegan" aria-hidden="true">
                <div class="pemandangan {{ $trkJenis === 'pohon' ? 'pohon ' : '' }}{{ $trkGaya }}">
                    @if ($trkJenis === 'pohon')
                        @foreach (range(0, 17) as $trkN)
                            <i style="--x: {{ $trkN * 5.6 - 2 }}%; --s: {{ [1.1, 1.5, 0.9, 1.3, 1.0, 1.7][$trkN % 6] }}; --d: {{ round(($trkN * 7 % 18) / 18 * 1.6, 2) }}"></i>
                        @endforeach
                    @elseif ($trkGaya === 'bara')
                        @foreach (range(0, 21) as $trkN)
                            <i style="--x: {{ round(fmod($trkN * 4.7 + 3, 100), 1) }}%; --s: {{ [0.8, 1.2, 0.6, 1.0][$trkN % 4] }}; --v: {{ [0.55, 0.9, 0.7, 1.0, 0.45][$trkN % 5] }}; --t: {{ round($trkN * 0.43, 2) }}"></i>
                        @endforeach
                    @elseif ($trkGaya === 'bumi')
                        <svg class="bumi-svg" viewBox="0 0 200 200" fill="none">
                            <defs>
                                <radialGradient id="bumiLaut" cx="35%" cy="30%" r="85%"><stop offset="0" stop-color="#5eaee0"/><stop offset="0.6" stop-color="#2b72b1"/><stop offset="1" stop-color="#143f6c"/></radialGradient>
                                <radialGradient id="bumiBayang" cx="50%" cy="50%" r="50%"><stop offset="0.62" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#04162b" stop-opacity="0.5"/></radialGradient>
                                <clipPath id="bumiPotong"><circle cx="100" cy="100" r="96"/></clipPath>
                                <g id="bumiBenua" fill="#46a566">
                                    <path d="M20 70C30 50 60 45 75 60C90 75 80 95 60 100C40 105 15 95 20 70Z"/>
                                    <path d="M110 40C130 30 160 40 165 60C170 80 145 90 130 85C110 80 95 55 110 40Z"/>
                                    <path d="M95 115C115 105 140 115 145 135C150 155 125 170 105 160C90 150 85 125 95 115Z"/>
                                    <path d="M40 125C55 118 70 125 68 140C65 155 45 158 38 148C32 140 33 130 40 125Z"/>
                                    <path d="M150 100C160 98 175 105 172 115C168 125 155 123 150 100Z"/>
                                </g>
                            </defs>
                            <circle cx="100" cy="100" r="99" fill="#9bd6ff" opacity="0.28"/>
                            <circle cx="100" cy="100" r="96" fill="url(#bumiLaut)"/>
                            <g clip-path="url(#bumiPotong)"><g class="bumi-darat"><use href="#bumiBenua"/><use href="#bumiBenua" x="260"/></g></g>
                            <circle cx="100" cy="100" r="96" fill="url(#bumiBayang)"/>
                        </svg>
                    @else
                        @foreach ([0.45, 0.7, 1, 1.3, 1.6] as $trkUkuran)
                            <i style="--s: {{ $trkUkuran }}"></i>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="isi-data">
                <div class="grid items-start gap-4 md:grid-cols-2">
                    @include('dashboard.partials.'.$trkPartial)
                </div>
            </div>
        </div>
    </section>
@endforeach

<div class="mt-4 grid gap-3">
    @include('dashboard.partials.catatan-data')
</div>
