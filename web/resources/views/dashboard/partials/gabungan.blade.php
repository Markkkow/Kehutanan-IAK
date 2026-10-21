@use('App\Support\Format', 'F')

@php
    $kekuatan = fn ($r) => match (true) {
        $r === null => 'tidak terhitung',
        abs($r) >= 0.7 => 'kuat',
        abs($r) >= 0.4 => 'sedang',
        default => 'lemah',
    };
    $rKarhutla = $benangMerah['r_karhutla_defor'];
    $rKayu = $benangMerah['r_kayu_defor'];
@endphp

<x-judul-panel judul="Analisis gabungan"
               sub="Hal-hal yang baru terlihat bila beberapa data disambungkan. Tren gabungan (indeks 2018 = 100) ada di Ringkasan.">
    <div class="flex flex-wrap items-center gap-1.5">
        <span data-ds="G2" class="lencana-ds"><x-ikon nama="api" class="size-3" /> G2 · D3 × D5</span>
        <span data-ds="G3" class="lencana-ds"><x-ikon nama="lapisan" class="size-3" /> G3 · D3 × D4 × D5</span>
    </div>
</x-judul-panel>

<div class="grid items-start gap-4 xl:grid-cols-3">
    {{-- G2: kebakaran vs hutan hilang (D3 + D5), dua garis di satu sumbu hektar --}}
    @php
        $ah = $apiHutan;
        $turunApi = ($ah['puncak']['karhutla'] - $ah['setelah']['karhutla']) / $ah['puncak']['karhutla'] * 100;
        $turunHutan = ($ah['puncak']['deforestasi'] - $ah['setelah']['deforestasi']) / $ah['puncak']['deforestasi'] * 100;
    @endphp
    <x-grafik id="chartApiHutan" jangkar="api-hutan" kode="G2" ikon="api" class="xl:col-span-2" tinggi="h-[320px]"
              judul="Apakah kebakaran besar diikuti hilangnya hutan?"
              ket="D3 + D5 · ha · nasional · {{ $ah['tahun'][0] }}–{{ end($ah['tahun']) }} (irisan kedua data) · deforestasi per periode dua tahun, digambar di tahun akhir periodenya">
        <x-slot:ringkas>
            Keduanya memuncak bersamaan pada {{ $ah['puncak']['tahun'] }}, lalu sama-sama turun tajam: karhutla rata-rata {{ F::persen($turunApi, 0) }} lebih kecil
            dan deforestasi {{ F::persen($turunHutan, 0) }} lebih kecil pada tahun-tahun sesudahnya.
        </x-slot:ringkas>
    </x-grafik>

    {{-- Kartu pendamping G2: angka puncak D3 dan D5 --}}
    <div data-ds="G2" class="kartu flex flex-col">
        <div class="flex items-center justify-between gap-2">
            <div class="kartu-judul flex items-center gap-1.5">
                <span class="lencana-ds">G2</span>
                <span>Kebakaran dan hutan hilang bergerak searah</span>
            </div>
            <div class="flex items-center -space-x-1">
                <span data-ds="D3" class="ikon-ds size-6 rounded-full"><x-ikon nama="api" class="size-3.5" /></span>
                <span data-ds="D5" class="ikon-ds size-6 rounded-full"><x-ikon nama="kapak" class="size-3.5" /></span>
            </div>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2.5">
            {{-- Kotak metrik D3 Karhutla --}}
            <div data-ds="D3" class="subkartu-ds">
                <div class="flex items-center justify-between gap-1">
                    <span class="lencana-ds">D3</span>
                    <x-ikon nama="api" class="size-3.5 text-[var(--ds-teks)]" />
                </div>
                <div class="mt-1.5 text-[11px] font-medium text-ink-2">Puncak {{ $ah['puncak']['tahun'] }}: karhutla</div>
                <div class="mt-0.5 text-xl font-bold tabular-nums">
                    {{ F::ringkas($ah['puncak']['karhutla']) }} <span class="text-xs font-normal text-ink-3">ha</span>
                </div>
            </div>
            {{-- Kotak metrik D5 Deforestasi --}}
            <div data-ds="D5" class="subkartu-ds">
                <div class="flex items-center justify-between gap-1">
                    <span class="lencana-ds">D5</span>
                    <x-ikon nama="kapak" class="size-3.5 text-[var(--ds-teks)]" />
                </div>
                <div class="mt-1.5 text-[11px] font-medium text-ink-2">Deforestasi {{ $ah['puncak']['tahun'] - 1 }}–{{ $ah['puncak']['tahun'] }}</div>
                <div class="mt-0.5 text-xl font-bold tabular-nums">
                    {{ F::ringkas($ah['puncak']['deforestasi']) }} <span class="text-xs font-normal text-ink-3">ha/th</span>
                </div>
            </div>
        </div>

        <x-insight :lipat="false" class="mt-3 border-0 bg-transparent p-0">
            <x-slot:terlihat>
                Tahun kebakaran terbesar ({{ $ah['puncak']['tahun'] }}) juga periode deforestasi tertinggi. Setelah itu kebakaran mengecil dan deforestasi
                ikut turun ke kisaran 100–120 rb ha/th. Korelasinya r = {{ F::angka($ah['r'], 2) }}.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Dugaan kami: kebakaran besar 2019 (tahun El Niño, kemarau panjang) menghabisi tutupan hutan, terutama di lahan gambut Sumatera dan
                Kalimantan. Turunnya deforestasi sesudahnya juga dipengaruhi hal lain, misalnya moratorium izin baru di hutan primer dan gambut (Inpres 5/2019).
            </x-slot:kenapa>
            <x-slot:lanjut>
                Ini pola nasional dari {{ count($ah['tahun']) }} titik waktu, bukan bukti sebab-akibat; data tidak menunjukkan lokasi yang terbakar lalu hilang hutannya.
            </x-slot:lanjut>
        </x-insight>
    </div>

    {{-- G3: benang merah (sintesis 3 dataset: D3 Karhutla + D4 Kayu + D5 Deforestasi) --}}
    <x-grafik id="chartBenangMerah" jangkar="benang-merah" kode="G3" ikon="lapisan" class="xl:col-span-2" tinggi="h-[340px]"
              judul="Benang merah per provinsi: karhutla, deforestasi, dan produksi kayu"
              ket="D3 + D4 + D5 · {{ $benangMerah['periode'] }} · titik = provinsi, warna = pulau">
        {{-- Pita pemetaan visual 3 dimensi dataset pada grafik gelembung G3 --}}
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <span data-ds="D3" class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--ds-garis)] bg-[var(--ds-lembut)] px-2.5 py-1 text-[11px] font-medium text-[var(--ds-teks)]">
                <x-ikon nama="api" class="size-3.5" /> <b>Sumbu X:</b> Karhutla (D3)
            </span>
            <span data-ds="D5" class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--ds-garis)] bg-[var(--ds-lembut)] px-2.5 py-1 text-[11px] font-medium text-[var(--ds-teks)]">
                <x-ikon nama="kapak" class="size-3.5" /> <b>Sumbu Y:</b> Deforestasi (D5)
            </span>
            <span data-ds="D4" class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--ds-garis)] bg-[var(--ds-lembut)] px-2.5 py-1 text-[11px] font-medium text-[var(--ds-teks)]">
                <x-ikon nama="kayu" class="size-3.5" /> <b>Ukuran Gelembung:</b> Produksi Kayu (D4)
            </span>
        </div>
        <x-slot:ringkas>
            @if ($rKayu > $rKarhutla)
                Produksi kayu lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }}, {{ $kekuatan($rKayu) }}) dibanding karhutla
                (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Korelasi bukan sebab-akibat.
            @else
                Karhutla lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKarhutla, 2) }}) dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).
                Korelasi bukan sebab-akibat.
            @endif
        </x-slot:ringkas>
        <x-slot:info>
            @if ($rKayu > $rKarhutla)
                <x-insight :lipat="false">
                    <x-slot:terlihat>
                        Di tingkat provinsi, produksi kayu justru lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }},
                        {{ $kekuatan($rKayu) }}) dibanding karhutla (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Riau dan Kalimantan
                        Timur, produsen kayu besar, juga paling banyak kehilangan hutan. Sebaliknya Nusa Tenggara Timur punya karhutla luas tetapi
                        deforestasinya kecil.
                    </x-slot:terlihat>
                    <x-slot:kenapa>
                        Provinsi produsen kayu adalah provinsi dengan konsesi hutan tanaman dan perkebunan terluas; pembukaan hutan alam untuk
                        ditanami ulang tercatat sebagai deforestasi. Kebakaran di NTT banyak terjadi di savana dan padang rumput yang memang bukan hutan.
                        Ingat: korelasi bukan sebab-akibat, dan hanya ada {{ $benangMerah['titik']->count() }} titik. Periode diseragamkan ke
                        {{ $benangMerah['periode'] }} (irisan ketiga data); Kalimantan Utara digabung ke Kalimantan Timur.
                    </x-slot:kenapa>
                </x-insight>
            @else
                <x-insight :lipat="false">
                    <x-slot:terlihat>
                        Provinsi dengan karhutla luas cenderung juga mengalami deforestasi besar (r = {{ F::angka($rKarhutla, 2) }}), lebih
                        erat dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).
                    </x-slot:terlihat>
                    <x-slot:kenapa>
                        Kebakaran di lahan gambut langsung menghilangkan tutupan hutan, sedangkan kayu banyak berasal dari hutan tanaman yang
                        dipanen dan ditanam ulang. Korelasi bukan sebab-akibat; jumlah provinsi juga sedikit.
                    </x-slot:kenapa>
                </x-insight>
            @endif
        </x-slot:info>
    </x-grafik>

    {{-- G3: angka korelasi; lencana menunjukkan pasangan data yang dikorelasikan --}}
    @php
        $kartuG3 = [
            [['D3', 'api'], 'r karhutla ↔ deforestasi', F::angka($rKarhutla, 2), 'hubungan '.$kekuatan($rKarhutla)],
            [['D4', 'kayu'], 'r produksi kayu ↔ deforestasi', F::angka($rKayu, 2), 'hubungan '.$kekuatan($rKayu)],
            [null, 'Provinsi (titik)', $benangMerah['titik']->count(), 'di grafik G3, '.$benangMerah['periode']],
        ];
    @endphp
    <div class="grid content-start gap-3">
        @foreach ($kartuG3 as [$pasangan, $label, $nilai, $arti])
            <div data-ds="G3" class="kartu flex items-center justify-between gap-3 p-3.5">
                <div>
                    <div class="flex flex-wrap items-center gap-1">
                        @if ($pasangan)
                            <span data-ds="{{ $pasangan[0] }}" class="lencana-ds"><x-ikon :nama="$pasangan[1]" class="size-3" />{{ $pasangan[0] }}</span>
                            <span class="text-[11px] text-ink-3">↔</span>
                            <span data-ds="D5" class="lencana-ds"><x-ikon nama="kapak" class="size-3" />D5</span>
                        @else
                            <span class="lencana-ds">G3</span>
                        @endif
                    </div>
                    <div class="mt-1.5 text-[12.5px] font-semibold text-ink">{{ $label }}</div>
                    <div class="text-[11px] text-ink-3">{{ $arti }}</div>
                </div>
                <div class="text-2xl font-bold tabular-nums">{{ $nilai }}</div>
            </div>
        @endforeach
        <p class="px-1 text-[11.5px] leading-relaxed text-ink-3">r = koefisien korelasi Pearson antarprovinsi: mendekati 1 = searah kuat, mendekati 0 = tidak ada hubungan.
            Korelasi bukan sebab-akibat.</p>
    </div>
</div>
