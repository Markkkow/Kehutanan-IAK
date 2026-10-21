@use('App\Support\Format', 'F')

@php
    // [kunci deret, ikon, label, nilai, satuan, keterangan, [perubahan, teks, naik-itu-baik?], kode]
    $tiles = [
        ['primer', 'pohon', 'Hutan primer '.$kpi['primer']['tahun'], F::ringkas($kpi['primer']['nilai']), 'ha',
            null, [$kpi['primer']['perubahan'], 'vs 2018', true], 'D1'],
        ['tutupan', 'daun', 'Tutupan hutan '.$kpi['tutupan']['tahun'], F::ringkas($kpi['tutupan']['nilai']), 'ha',
            F::persen($kpi['tutupan']['persen']).' daratan', [$kpi['tutupan']['perubahan'], 'vs 2018', true], 'D2'],
        ['karhutla', 'api', 'Kebakaran hutan & lahan '.$kpi['karhutla']['tahun'], F::ringkas($kpi['karhutla']['nilai']), 'ha',
            null, [$kpi['karhutla']['perubahan'], 'vs 2018', false], 'D3'],
        ['kayu', 'kayu', 'Produksi kayu bulat '.$kpi['kayu']['tahun'], F::ringkas($kpi['kayu']['nilai']), 'm³',
            null, [$kpi['kayu']['perubahan'], 'vs 2018', null], 'D4'],
        ['deforestasi', 'kapak', 'Deforestasi netto '.str_replace('-', '–', $kpi['deforestasi']['periode']), F::ringkas($kpi['deforestasi']['nilai']), 'ha/th',
            null, [$kpi['deforestasi']['perubahan'], 'vs 2018–2019', false], 'D5'],
    ];
    $temuan = [
        ['kelas-lahan', 'pohon', 'D1', 'Hutan bertambah, hutan primer berkurang',
            'Total hutan naik '.F::ringkas($kelas['hutan_akhir'] - $kelas['hutan_awal']).' ha ('.$kelas['awal'].'–'.$kelas['akhir'].'), tetapi hutan primer turun '.F::ringkas(abs($kelas['selisih']['primer'])).' ha. Yang bertambah hutan sekunder dan hutan tanaman.'],
        ['karhutla', 'api', 'D3', 'Karhutla meledak di tahun El Niño',
            'Luas terbakar '.$karhutla['puncak'].' mencapai '.F::ringkas($karhutla['nasional'][$karhutla['puncak']]).' ha dan '.$karhutla['puncak_kedua'].' '.F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]).' ha, beberapa kali lipat tahun lain.'],
        ['deforestasi', 'kapak', 'D5', 'Laju deforestasi turun tajam sejak 2019',
            'Dari '.F::ringkas($kpi['deret']['deforestasi']['2018-2019'] ?? null).' ha/th (2018–2019) menjadi '.F::ringkas($kpi['deforestasi']['nilai']).' ha/th ('.str_replace('-', '–', $kpi['deforestasi']['periode']).').'],
        ['benang-merah', 'kayu', 'G3', 'Produksi kayu paling dekat dengan deforestasi',
            'Antarprovinsi, kayu ↔ deforestasi r = '.F::angka($benangMerah['r_kayu_defor'], 2).', lebih kuat dari karhutla ↔ deforestasi (r = '.F::angka($benangMerah['r_karhutla_defor'], 2).').'],
        ['catatan-validasi', 'lapisan', 'D2 vs D5', 'Dua sumber resmi tidak sepakat',
            'Tutupan hutan (D2) naik '.F::ringkas($validasi['total_d2']).' ha ('.$validasi['periode'].'), padahal deforestasi netto (D5) menunjukkan hutan berkurang.'],
    ];
    $panelIndeks = [
        ['Hutan primer (D1)', 'primer', 'pohon', 'D1'],
        ['Tutupan hutan (D2)', 'tutupan', 'daun', 'D2'],
        ['Karhutla (D3)', 'karhutla', 'api', 'D3'],
        ['Produksi kayu (D4)', 'kayu', 'kayu', 'D4'],
        ['Deforestasi netto (D5)', 'deforestasi', 'kapak', 'D5'],
    ];
@endphp

{{-- Banner dengan ilustrasi hutan --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#285436] via-[#4a7c59] to-[#6f9a78] px-6 pt-5 pb-16 text-[#f6f1e4] shadow-sm md:px-8 md:pt-6 md:pb-20">
    <div class="absolute inset-x-0 bottom-0 h-[55%]"><x-lanskap-hutan /></div>
    <div class="relative max-w-2xl">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-black/20 px-3 py-1 text-[11.5px] font-medium backdrop-blur">
            <x-ikon nama="pohon" class="size-3.5" /> Integrasi 5 sumber data · 2018–2026 (2026: Januari–Juli) · 34 provinsi
        </span>
        <h1 class="mt-3 text-balance text-[24px] leading-[1.15] font-semibold text-white drop-shadow-sm md:text-[26px]">
            Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?
        </h1>
        <p class="mt-2 max-w-xl text-[14px] leading-relaxed text-[#eef3e6]/90">
            Data BPS dan KLHK dalam empat format file (XLS, XLSX, CSV, JSON) diekstrak, diseragamkan, lalu dimuat ke satu database.
        </p>
    </div>
</div>

{{-- 5 Kartu KPI dengan tema masing-masing dataset (D1–D5) --}}
<div class="relative z-10 -mt-12 grid grid-cols-1 gap-4 px-2 sm:grid-cols-2 md:px-4 lg:grid-cols-5">
    @foreach ($tiles as [$kunci, $ikon, $label, $nilai, $satuan, $catatan, $delta, $kode])
        @php $baik = null; if ($delta) { [$v, $teks, $baikNaik] = $delta; $baik = $baikNaik === null ? 'netral' : (($baikNaik ? $v > 0 : $v < 0) ? 'baik' : 'buruk'); } @endphp
        <div data-ds="{{ $kode }}" class="tile">
            <div class="relative z-10 flex items-start justify-between gap-2">
                <span class="tile-tag">{{ $kode }}</span>
                <x-ikon :nama="$ikon" class="size-6 opacity-80" />
            </div>
            <div class="tile-judul relative z-10">{{ $label }}</div>
            <div class="tile-angka relative z-10">{{ $nilai }}<small>{{ $satuan }}</small></div>
            @if ($kode === 'D2')
                <div class="tile-seg relative z-10" style="--pct: {{ round((float) $kpi['tutupan']['persen']) }}" role="img" aria-label="{{ F::persen($kpi['tutupan']['persen']) }} daratan"></div>
            @endif
            <div class="relative z-10 mt-2 flex min-h-6 flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                @if ($delta)
                    <span class="tile-delta" data-arah="{{ $baik }}">{{ $v > 0 ? '↑' : '↓' }} {{ F::persen(abs($v), abs($v) >= 10 ? 0 : 1) }}</span>
                    <span class="tile-ket">{{ $teks }}</span>
                @endif
                @if ($catatan)<span class="tile-ket">{{ $catatan }}</span>@endif
            </div>
            <div class="tile-spark relative z-10"><canvas id="spark-{{ $kunci }}" aria-hidden="true"></canvas></div>
        </div>
    @endforeach
</div>

<div class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    {{-- Tren gabungan 5 data; tiap panel memakai warna khas datanya --}}
    <div class="kartu">
        <div class="flex items-center gap-2">
            <span class="grid size-8 place-items-center rounded-lg bg-brand-lembut text-brand-teks"><x-ikon nama="grafik" class="size-4" /></span>
            <div class="kartu-judul">Tren gabungan, indeks 2018 = 100</div>
        </div>
        <div class="kartu-ket mt-2">Satuan tiap data berbeda, sehingga semuanya diubah ke indeks (nilai tahun itu ÷ nilai 2018 × 100).</div>
        {{-- 5 panel dalam 2 kolom; panel terakhir selebar kartu agar tidak ada ruang kosong --}}
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($panelIndeks as [$judul, $kunci, $ikon, $kodePanel])
                <div data-ds="{{ $kodePanel }}" @class(['subkartu-ds', 'sm:col-span-2' => $loop->last])>
                    <div class="flex items-center gap-1.5 text-[12.5px] font-semibold text-ink">
                        <span class="ikon-ds size-6 rounded-lg"><x-ikon :nama="$ikon" class="size-3.5" /></span>
                        <span>{{ $judul }}</span>
                    </div>
                    <div class="mt-1.5 h-28"><canvas id="indeks-{{ $kunci }}" aria-label="Indeks {{ $judul }}"></canvas></div>
                </div>
            @endforeach
        </div>
        <details class="lihat-tabel" data-tabel-indeks>
            <summary>Lihat tabel data</summary>
            <div class="isi"></div>
        </details>
    </div>

    {{-- Temuan utama: warna & lencana mengikuti data/analisis asalnya; temuan lintas sumber (D2 vs D5) memakai warna netral --}}
    <div class="kartu">
        <div class="kartu-judul">Temuan utama</div>
        <ol class="grid gap-2.5">
            @foreach ($temuan as [$tab, $ikon, $kodeTemuan, $judul, $isi])
                <li>
                    <a href="#{{ $tab }}" @if (in_array($kodeTemuan, ['D1', 'D2', 'D3', 'D4', 'D5', 'G2', 'G3'])) data-ds="{{ $kodeTemuan }}" @endif
                       class="subkartu-ds flex gap-3 p-3 hover:bg-sorot">
                        <span class="ikon-ds size-8 rounded-lg"><x-ikon :nama="$ikon" class="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5 text-[13.5px] font-semibold text-ink">
                                <span class="lencana-ds">{{ $kodeTemuan }}</span>
                                <span>{{ $judul }}</span>
                            </span>
                            <span class="mt-1 block text-[12.5px] leading-relaxed text-ink-2">{{ $isi }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</div>
