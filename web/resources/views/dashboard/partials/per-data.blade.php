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
                        <div class="bumi-img"></div>
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
