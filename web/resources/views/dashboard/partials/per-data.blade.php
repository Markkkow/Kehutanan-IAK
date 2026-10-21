<x-judul-panel judul="Analisis per data"
               sub="Gulir pelan: tiap data punya adegan sendiri, lalu grafiknya muncul. Latar berganti seiring data." />

@php
    // [kode, partial, judul adegan, kalimat adegan, judul data, ringkas, jenis pemandangan, kelas]
    $trek = [
        ['D1', 'kelas-lahan', 'Dari dekat, hutan adalah pohon.', 'Tumbuh satu per satu. Tetapi mana yang benar-benar hutan primer?', 'Hutan primer dan kelas lahan', 'Total hutan naik, tetapi yang bertambah bukan hutan primer.', 'pohon', 'tumbuh'],
        ['D2', 'tutupan', 'Dari satelit, terlihat yang sebenarnya.', 'Hijau meluas di peta. Berapa persen daratan yang masih berhutan?', 'Tutupan hutan', 'Persen daratan berhutan, dan provinsi yang naik atau turun.', 'lingkaran', 'meluas'],
        ['D3', 'karhutla', 'Ketika kemarau panjang, api menjalar.', 'Dua tahun El Niño membakar lebih dari sisa tahun lainnya.', 'Kebakaran hutan dan lahan', 'Dua lonjakan besar, sisanya jauh lebih kecil.', 'bara', 'bara'],
        ['D4', 'kayu', 'Dari hutan ke gergaji.', 'Setiap lingkaran tahun adalah kayu yang diangkut keluar.', 'Produksi kayu bulat', 'Naik hingga 2023, dan terpusat di sedikit provinsi.', 'lingkaran', 'cincin'],
        ['D5', 'deforestasi', 'Yang hilang tidak kembali begitu saja.', 'Laju hilangnya hutan turun, tetapi angkanya masih besar.', 'Deforestasi netto', 'Laju turun tajam setelah 2019.', 'pohon', 'hilang'],
    ];
@endphp

@foreach ($trek as [$kode, $partial, $judulAdegan, $kalimat, $judulData, $ringkas, $jenis, $kelas])
    <section class="trek" id="slide-{{ strtolower($kode) }}" data-trek="{{ $loop->index }}" data-ds="{{ $kode }}" aria-label="{{ $judulData }}">
        <div class="panggung">
            <div class="adegan">
                <span class="tile-tag">{{ $kode }}</span>
                <h2>{{ $judulAdegan }}</h2>
                <p>{{ $kalimat }}</p>
                <div class="pemandangan {{ $jenis === 'pohon' ? 'pohon ' : '' }}{{ $kelas }}" aria-hidden="true">
                    @if ($jenis === 'pohon')
                        @foreach (range(0, 17) as $n)
                            <i style="--x: {{ $n * 5.6 - 2 }}%; --s: {{ [1.1, 1.5, 0.9, 1.3, 1.0, 1.7][$n % 6] }}; --d: {{ round(($n * 7 % 18) / 18 * 1.6, 2) }}"></i>
                        @endforeach
                    @elseif ($kelas === 'bara')
                        @foreach (range(0, 21) as $n)
                            <i style="--x: {{ ($n * 4.7 + 3) % 100 }}%; --s: {{ [0.8, 1.2, 0.6, 1.0][$n % 4] }}; --v: {{ [0.55, 0.9, 0.7, 1.0, 0.45][$n % 5] }}"></i>
                        @endforeach
                    @else
                        @foreach ([0.45, 0.7, 1, 1.3, 1.6] as $n => $sk)
                            <i style="--s: {{ $sk }}"></i>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="isi-data">
                <div class="kepala-data">
                    <span class="tile-tag">{{ $kode }}</span>
                    <h3>{{ $judulData }}</h3>
                    <p>{{ $ringkas }}</p>
                </div>
                <div class="grid items-start gap-4 md:grid-cols-2">
                    @include('dashboard.partials.'.$partial)
                </div>
            </div>
        </div>
    </section>
@endforeach

<div class="mt-4 grid gap-3">
    @include('dashboard.partials.catatan-data')
</div>
