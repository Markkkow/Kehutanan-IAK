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
        <span class="jangkar" aria-hidden="true"></span>
        <div class="panggung">
            <div class="latar-adegan" aria-hidden="true">
                @if ($trkJenis === 'pohon' || $trkGaya === 'bara')
                    {{-- pohon: D1 naik, D5 turun, D3 (kebakaran) diam di belakang titik api --}}
                    <div class="pemandangan pohon {{ $trkJenis === 'pohon' ? $trkGaya : 'diam' }}">
                        @foreach (range(0, 17) as $trkN)
                            <i style="--x: {{ $trkN * 5.6 - 2 }}%; --s: {{ [1.1, 1.5, 0.9, 1.3, 1.0, 1.7][$trkN % 6] }}; --d: {{ round(($trkN * 7 % 18) / 18 * 1.6, 2) }}"></i>
                        @endforeach
                    </div>
                @endif
                @if ($trkGaya === 'bara')
                    <div class="pemandangan bara">
                        @foreach (range(0, 69) as $trkN)
                            <i style="--x: {{ round(fmod($trkN * 7.3 + 2, 100), 1) }}%; --s: {{ [0.7, 1.1, 0.6, 1.4, 0.9][$trkN % 5] }}; --v: {{ [0.55, 0.9, 0.7, 1.0, 0.45, 0.8, 0.62][$trkN % 7] }}; --t: {{ round($trkN * 0.37, 2) }}; --c: hsl({{ 16 + ($trkN * 13 % 41) }} 100% {{ 52 + ($trkN * 5 % 9) }}%)"></i>
                        @endforeach
                    </div>
                @elseif ($trkGaya === 'bumi')
                    <div class="pemandangan bumi"><div class="bumi-img"></div></div>
                @elseif ($trkGaya === 'cincin')
                    <div class="pemandangan cincin">
                        @foreach ([0.45, 0.7, 1, 1.3, 1.6] as $trkUkuran)
                            <i style="--s: {{ $trkUkuran }}"></i>
                        @endforeach
                    </div>
                @endif
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
