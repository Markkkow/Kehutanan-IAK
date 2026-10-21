@use('App\Support\Format', 'F')

@php
    // Isi tiap bab diambil dari data yang sama dengan kartu grafik; kalimatnya mengikuti penjelasan ⓘ yang sudah ada.
    // Nama variabel sengaja tidak memakai $kelas/$tutupan/... agar tidak menimpa data yang dipakai partial grafik.
    $babSel = $kelas['selisih'];
    $babT0 = $tutupan['nasional']->first();
    $babT1 = $tutupan['nasional']->last();
    $babApi = $karhutla['nasional'];
    $babKayu = $kayu['nasional'];
    $babDefor = $deforestasi['nasional'];
    $babDeforPuncak = $babDefor->firstWhere('periode', $deforestasi['puncak']);
    $babDeforAkhir = $babDefor->last();
    $babDeforUbah = ($babDeforAkhir['total'] - $babDeforPuncak['total']) / $babDeforPuncak['total'] * 100;
    $periode = fn ($p) => str_replace('-', '–', $p);

    $daftarBab = [
        [
            'kode' => 'D1', 'ikon' => 'pohon', 'label' => 'Tutupan lahan menurut kelas', 'sumber' => 'BPS/KLHK · XLS · nasional',
            'judul' => 'Hutannya bertambah, tapi hutan primernya berkurang.',
            'angka' => $babSel['primer'], 'format' => 'ringkas', 'tanda' => true, 'satuan' => 'ha',
            'ketAngka' => "hutan primer, {$kelas['awal']} → {$kelas['akhir']}",
            'slide' => [
                ['Terlihat', 'Total hutan naik '.F::ringkas($kelas['hutan_akhir'] - $kelas['hutan_awal']).' ha, tetapi hutan primer turun '.F::ringkas(abs($babSel['primer'])).' ha. Yang bertambah hutan sekunder (+'.F::ringkas($babSel['sekunder']).') dan hutan tanaman (+'.F::ringkas($babSel['tanaman']).').'],
                ['Kenapa', 'Menurut keterangan BPS, hutan tanaman adalah hasil budidaya (hutan tanaman industri maupun reboisasi). Hutan sekunder bertambah ketika bekas tebangan atau semak tumbuh kembali. Turunnya hutan primer bersamaan dengan naiknya hutan sekunder menurut kami lebih mungkin perpindahan kelas (dugaan kami).'],
                ['Lanjut', 'Total hutan yang sama dilihat per provinsi di D2: apakah kenaikannya merata?'],
            ],
            'grafik' => 'dashboard.partials.kelas-lahan',
        ],
        [
            'kode' => 'D2', 'ikon' => 'daun', 'label' => 'Tutupan hutan per provinsi', 'sumber' => 'BPS/KLHK · CSV',
            'judul' => 'Tutupan hutan naik, tapi tidak merata.',
            'angka' => $babT1['hutan'] - $babT0['hutan'], 'format' => 'ringkas', 'tanda' => true, 'satuan' => 'ha',
            'ketAngka' => "tutupan hutan nasional, {$babT0['tahun']} → {$babT1['tahun']}",
            'slide' => [
                ['Terlihat', 'Tutupan hutan nasional naik dari '.F::ringkas($babT0['hutan']).' ha ('.F::persen($babT0['persen']).' daratan) menjadi '.F::ringkas($babT1['hutan']).' ha ('.F::persen($babT1['persen']).'). Tetapi '.$tutupan['jumlah_turun'].' dari '.$tutupan['jumlah_provinsi'].' provinsi tetap kehilangan hutan.'],
                ['Kenapa', 'Data ini hasil rekalkulasi KLHK: semua tahun dihitung ulang dengan metode yang sama, dan hutan tanaman (mis. HTI akasia dan eukaliptus) ikut dihitung sebagai hutan. Rincian D1 menunjukkan kenaikannya datang dari hutan sekunder dan hutan tanaman.'],
                ['Lanjut', 'Kalau tutupan naik, apa yang masih menggerus hutan? Dua tekanan yang diperiksa: kebakaran (D3) dan produksi kayu (D4).'],
            ],
            'grafik' => 'dashboard.partials.tutupan',
        ],
        [
            'kode' => 'D3', 'ikon' => 'api', 'label' => 'Kebakaran hutan & lahan', 'sumber' => 'SiPongi KLHK · XLSX',
            'judul' => 'Api datang bergelombang.',
            'angka' => $babApi[$karhutla['puncak']], 'format' => 'ringkas', 'tanda' => false, 'satuan' => 'ha',
            'ketAngka' => "terbakar pada {$karhutla['puncak']}, tahun terluas",
            'slide' => [
                ['Terlihat', 'Ada dua lonjakan: '.$karhutla['puncak'].' ('.F::ringkas($babApi[$karhutla['puncak']]).' ha) dan '.$karhutla['puncak_kedua'].' ('.F::ringkas($babApi[$karhutla['puncak_kedua']]).' ha); tahun lain jauh lebih kecil. Data 2026 baru sampai Juli, jadi tidak dibandingkan dengan tahun penuh.'],
                ['Kenapa', '2019 dan 2023 adalah tahun El Niño: kemarau lebih panjang dan kering, sehingga lahan gambut di Kalimantan dan Sumatera mudah terbakar dan sulit dipadamkan. Pembukaan lahan dengan cara membakar memperparah kondisi ini.'],
                ['Lanjut', 'Kebakaran bersifat musiman. Tekanan yang lebih rutin datang dari produksi kayu (D4).'],
            ],
            'grafik' => 'dashboard.partials.karhutla',
        ],
        [
            'kode' => 'D4', 'ikon' => 'kayu', 'label' => 'Produksi kayu bulat', 'sumber' => 'BPS · JSON',
            'judul' => 'Produksi kayu naik hampir setiap tahun.',
            'angka' => $babKayu[$kayu['puncak']], 'format' => 'ringkas', 'tanda' => false, 'satuan' => 'm³',
            'ketAngka' => "kayu bulat pada {$kayu['puncak']}, produksi tertinggi",
            'slide' => [
                ['Terlihat', 'Produksi naik terus hingga puncaknya pada '.$kayu['puncak'].' ('.F::ringkas($babKayu[$kayu['puncak']]).' m³), lalu turun. Pada '.$kayu['tahun']->last().', '.$kayu['teratas'].' sendiri menghasilkan '.F::persen($kayu['teratas_persen'], 0).' kayu bulat nasional.'],
                ['Kenapa', 'Sebagian besar kayu bulat berasal dari hutan tanaman industri untuk bahan baku pulp dan kertas, terutama di Riau, Sumatera Selatan, dan Kalimantan. Produksi tinggi tidak otomatis berarti hutan alam rusak, karena banyak yang ditebang memang ditanam untuk dipanen.'],
                ['Lanjut', 'Seberapa banyak hutan yang benar-benar hilang? Data D5 melihat laju deforestasi netto.'],
            ],
            'grafik' => 'dashboard.partials.kayu',
        ],
        [
            'kode' => 'D5', 'ikon' => 'kapak', 'label' => 'Deforestasi netto', 'sumber' => 'BPS/KLHK · CSV',
            'judul' => 'Laju hilangnya hutan melambat.',
            'angka' => $babDeforUbah, 'format' => 'persen', 'tanda' => true, 'satuan' => '',
            'ketAngka' => 'deforestasi netto '.$periode($babDeforAkhir['periode']).' dibanding puncaknya '.$periode($babDeforPuncak['periode']),
            'slide' => [
                ['Terlihat', 'Deforestasi netto memuncak pada '.$periode($deforestasi['puncak']).' ('.F::ringkas($babDeforPuncak['total']).' ha/th), lalu turun tajam ke '.F::ringkas($babDeforAkhir['total']).' ha/th. '.F::persen($deforestasi['dalam_persen'], 0).' terjadi di dalam kawasan hutan.'],
                ['Kenapa', 'Penurunan sejak 2019–2020 bertepatan dengan moratorium permanen izin baru di hutan primer dan gambut (Inpres 5/2019) serta tahun-tahun basah 2020–2022. Lonjakan 2018–2019 sejalan dengan kebakaran besar 2019 (D3).'],
                ['Lanjut', 'Apakah kebakaran dan produksi kayu berhubungan dengan hilangnya hutan? Lihat Analisis gabungan.'],
            ],
            'grafik' => 'dashboard.partials.deforestasi',
        ],
    ];

    // Angka besar di pembuka: tampil final tanpa JavaScript; animasi menghitungnya dari 0.
    $tulisAngka = function ($b) {
        $v = $b['angka'];
        $teks = $b['format'] === 'persen' ? F::persen(abs($v), 0) : F::ringkas(abs($v));
        $tanda = $v < 0 ? '−' : ($b['tanda'] ? '+' : '');

        return $tanda.$teks;
    };
@endphp

<div class="bab-pengantar">
    <x-judul-panel judul="Lima data, lima cerita"
                   sub="Gulir pelan: tiap data dibuka dengan temuan utamanya, lalu pembahasan (terlihat → kenapa → lanjut), lalu grafiknya." />
</div>

{{-- Penanda posisi bab di sisi kanan (layar lebar) --}}
<nav class="bab-rel" aria-label="Bab per data" data-bab-rel>
    @foreach ($daftarBab as $b)
        <a href="#bab-{{ strtolower($b['kode']) }}" data-ds="{{ $b['kode'] }}" data-rel="bab-{{ strtolower($b['kode']) }}">
            <span class="bab-rel-isi"><i></i></span><b>{{ $b['kode'] }}</b>
        </a>
    @endforeach
</nav>

@foreach ($daftarBab as $b)
    <section class="bab" id="bab-{{ strtolower($b['kode']) }}" data-ds="{{ $b['kode'] }}" data-bab>
        {{-- 1. Pembuka: judul temuan + angka kunci --}}
        <div class="bab-pembuka" data-bab-pembuka>
            @include('dashboard.partials.bab-latar', ['kode' => $b['kode']])
            <div class="bab-sinar" aria-hidden="true"></div>
            <div class="bab-pembuka-isi">
                <p class="bab-kode"><x-ikon :nama="$b['ikon']" class="size-4" /><span>{{ $b['kode'] }}</span>{{ $b['label'] }}</p>
                <h2 class="bab-judul">@foreach (explode(' ', $b['judul']) as $kata)<span class="kata"><span>{{ $kata }}</span></span> @endforeach</h2>
                <p class="bab-angka">
                    <span data-hitung="{{ $b['angka'] }}" data-format="{{ $b['format'] }}" data-tanda="{{ $b['tanda'] ? 1 : 0 }}">{{ $tulisAngka($b) }}</span>@if ($b['satuan'])<small>{{ $b['satuan'] }}</small>@endif
                </p>
                <p class="bab-ket">{{ $b['ketAngka'] }} <span>· {{ $b['sumber'] }}</span></p>
            </div>
            <div class="bab-gulir" aria-hidden="true"><span></span></div>
        </div>

        {{-- 2. Pembahasan: tiga kartu tampil sekaligus (statis, tanpa digeser/digulir) --}}
        <div class="bab-slide" data-bab-slide>
            <div class="bab-slide-kepala">
                <span>Pembahasan {{ $b['kode'] }}</span>
            </div>
            <div class="bab-slide-jalur">
                @foreach ($b['slide'] as $n => [$jenis, $isi])
                    <article class="bab-kartu">
                        <div class="bab-kartu-kepala"><span>0{{ $n + 1 }}</span>{{ $jenis }}</div>
                        <p>{{ $isi }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        {{-- 3. Grafik: kartu yang sudah ada (tren nasional + per provinsi) --}}
        <div class="bab-grafik grid items-start gap-3 md:grid-cols-2" data-bab-grafik>
            @include($b['grafik'])
        </div>
    </section>
@endforeach
