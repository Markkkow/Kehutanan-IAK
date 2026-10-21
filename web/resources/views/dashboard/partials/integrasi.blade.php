@use('App\Support\Format', 'F')

<x-judul-panel judul="Integrasi data"
               sub="Bagaimana lima file sumber menjadi satu dashboard: tiga teknik Data-level EAI (Transformation, Replication, Synchronization), semuanya langsung antar-database." />

@php
    $etl = $integrasi['etl'];
    $dibaca = $etl->where('tahap', 'extract')->sum('baris_masuk');
    $tl = $etl->where('tahap', 'transform-load')->where('dataset', '!=', 'REF');
    $tlOk = $tl->isNotEmpty() && $tl->every(fn ($l) => $l->status === 'OK');
    $rep = $integrasi['replikasi'];
    $repOk = $rep->where('status', 'SINKRON')->count();
    $barisFakta = $rep->filter(fn ($r) => str_starts_with($r->tabel, 'fakta_'))->sum('baris_master');
    $sync = $integrasi['sync'];
    $syncDiubah = $sync->sum(fn ($s) => $s->baris_baru + $s->baris_berubah + $s->baris_dihapus);
    // simpul alur: [ikon, judul, keterangan, angka]
    $simpul = [
        'sumber' => ['ringkasan', 'File sumber', '5 data · XLS, XLSX, CSV, JSON', F::angka($dibaca).' baris dibaca'],
        'staging' => ['basisdata', 'Staging', 'kehutanan_staging · teks mentah apa adanya', 'belum dibersihkan'],
        'master' => ['basisdata', 'Master', 'kehutanan_master · data bersih, kode provinsi BPS', F::angka($barisFakta).' baris fakta'],
        'replika' => ['basisdata', 'Replika', 'kehutanan_dashboard · salinan master', 'replikasi script'],
        'web' => ['grafik', 'Website', 'Laravel · hanya membaca replika', 'dashboard ini'],
    ];
    // panah antar-simpul: [nama tahap, teknik EAI?]
    $panah = [['Extract', false], ['Transformation', true], ['Replication', true], ['Dibaca', false]];
    $ikonDs = ['D1' => 'pohon', 'D2' => 'daun', 'D3' => 'api', 'D4' => 'kayu', 'D5' => 'kapak'];
@endphp

{{-- Alur integrasi: mendatar di layar lebar, menurun di layar kecil. Synchronization masuk ke Master dari bawah. --}}
<div class="kartu p-4 md:p-5">
    <div class="grid items-center gap-2 xl:grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr_auto_1fr] xl:gap-x-1 xl:gap-y-2">
        @foreach (array_keys($simpul) as $i => $kunci)
            @php [$ikon, $judulSimpul, $ketSimpul, $angka] = $simpul[$kunci]; $utama = in_array($kunci, ['master', 'replika']); @endphp
            <div @class(['rounded-xl border p-3 xl:row-start-1', 'border-brand/40 bg-brand-lembut' => $utama, 'border-garis bg-surface-2' => ! $utama])>
                <div class="flex items-center gap-2 text-[13px] font-semibold text-ink"><x-ikon :nama="$ikon" class="size-4 text-brand-teks" />{{ $judulSimpul }}</div>
                <div class="mt-1 text-[11.5px] leading-snug text-ink-3">{{ $ketSimpul }}</div>
                <div class="mt-1.5 text-[12px] font-medium text-ink-2">{{ $angka }}</div>
                @if ($kunci === 'sumber')
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach (['D1', 'D2', 'D3', 'D4', 'D5'] as $kd)
                            <span data-ds="{{ $kd }}" class="lencana-ds">{{ $kd }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($kunci === 'master')
                {{-- cabang Synchronization: di layar lebar berada tepat di bawah kotak Master --}}
                <div class="flex flex-col items-center gap-1 xl:col-start-5 xl:row-start-2">
                    <span class="hidden text-lg leading-none text-brand-teks xl:block">↑</span>
                    <span class="rounded-full bg-brand px-2.5 py-0.5 text-[11px] font-semibold text-white">Synchronization</span>
                    <span class="text-lg leading-none text-brand-teks xl:hidden">↑</span>
                    <div class="w-full rounded-xl border border-dashed border-brand/40 bg-surface-2 p-2.5 text-center">
                        <div class="text-[12.5px] font-semibold text-ink">File sumber terbaru (D1–D5)</div>
                        <div class="text-[11px] text-ink-3">bila penerbit memperbarui angka</div>
                    </div>
                </div>
            @endif
            @if (isset($panah[$i]))
                @php [$tahap, $teknik] = $panah[$i]; @endphp
                <div class="flex items-center justify-center gap-1 py-0.5 xl:row-start-1 xl:flex-col xl:px-1">
                    <span @class(['rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap', 'bg-brand text-white' => $teknik, 'bg-surface-2 text-ink-3 ring-1 ring-garis' => ! $teknik])>{{ $tahap }}</span>
                    <span class="text-lg leading-none text-ink-3"><span class="xl:hidden">↓</span><span class="hidden xl:inline">→</span></span>
                </div>
            @endif
        @endforeach
    </div>
    <p class="mt-3 text-[11.5px] text-ink-3"><span class="mr-1 inline-block rounded-full bg-brand px-2 py-px text-[10.5px] font-semibold text-white">hijau</span>
        = tiga teknik Data-level EAI (materi TM 9). Semua perpindahan data terjadi langsung antar-database; website tidak mengubah data apa pun.</p>
</div>

{{-- Tiga teknik dengan bahasa sederhana + bukti dari pipeline terakhir --}}
<div class="mt-3 grid gap-3 lg:grid-cols-3">
    @foreach ([
        ['Transformation', 'Merapikan data mentah dari lima sumber yang formatnya berbeda-beda menjadi satu bentuk yang seragam.',
            'Angka, nama provinsi, satuan (ribu ha → ha), dan bentuk tabel diseragamkan, lalu jumlahnya dicocokkan dengan total di sumber (aturan T1–T8).',
            $tlOk, $tlOk ? 'Semua cek total cocok dengan sumber' : 'Ada cek total yang perlu diperiksa'],
        ['Replication', 'Menyalin database secara terus-menerus ke server lain, sehingga website membaca salinannya, bukan data aslinya.',
            'Script menyalin perubahan schema Master ke schema replika di Supabase, lalu mencocokkan checksum keduanya.',
            $rep->isNotEmpty() && $repOk === $rep->count(),
            $repOk.' dari '.$rep->count().' tabel identik'],
        ['Synchronization', 'Menjaga data tetap sama dengan sumbernya: hanya bagian yang berubah yang diperbarui, satu arah.',
            'File sumber terbaru kelima data dibandingkan dengan Master baris per baris; hanya baris yang baru, berubah, atau hilang yang diterapkan.',
            $sync->isNotEmpty(), $sync->isNotEmpty() ? $sync->count().' data · '.F::angka($sync->sum('baris_dicek')).' baris dicek · '.$syncDiubah.' diterapkan' : 'Belum pernah dijalankan'],
    ] as [$teknik, $apa, $diSini, $ok, $bukti])
        <div class="kartu flex flex-col">
            <div class="flex items-center gap-2"><span class="rounded-full bg-brand px-2.5 py-0.5 text-[11.5px] font-semibold text-white">{{ $teknik }}</span></div>
            <p class="mt-2.5 text-[13px] leading-relaxed text-ink"><b class="font-semibold">Apa itu:</b> {{ $apa }}</p>
            <p class="mt-1.5 text-[13px] leading-relaxed text-ink-2"><b class="font-semibold text-ink">Di proyek ini:</b> {{ $diSini }}</p>
            <div class="mt-auto pt-3"><span class="{{ $ok ? 'pil-ok' : 'pil-cek' }}">{{ $ok ? '✓' : '!' }} {{ $bukti }}</span></div>
        </div>
    @endforeach
</div>

{{-- Bukti lengkap untuk pemeriksa: dilipat agar halaman tetap ringkas --}}
<details class="detail-teknis mt-3">
    <summary>Lihat detail teknis (sumber data, sinkronisasi, replikasi, log ETL)</summary>
<div class="mt-3 grid gap-3">
    <div class="grid gap-3 xl:grid-cols-3">
        <div class="kartu overflow-x-auto xl:col-span-2">
            <div class="kartu-judul">Sumber data</div>
            <div class="kartu-ket">Lima sumber, empat format file berbeda</div>
            <table class="tabel-data">
                <thead><tr><th>Kode</th><th>Data</th><th>Penerbit</th><th>Format</th><th>Cara extract</th></tr></thead>
                <tbody>
                @foreach ($integrasi['sumber'] as $s)
                    <tr data-ds="{{ $s['kode'] }}">
                        <td><span class="lencana-ds"><x-ikon :nama="$ikonDs[$s['kode']] ?? 'pohon'" class="size-3" />{{ $s['kode'] }}</span></td>
                        <td class="font-medium text-ink">{{ $s['nama'] }}</td>
                        <td>{{ $s['penerbit'] }}</td>
                        <td><span class="lencana-ds">{{ $s['format'] }}</span></td>
                        <td>{{ $s['cara'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="kartu overflow-x-auto">
            <div class="kartu-judul">Sinkronisasi file sumber → master</div>
            @if ($sync->isNotEmpty())
                <div class="kartu-ket">Terakhir: {{ $sync->first()->waktu }} · satu arah (file terbaru → master); yang sama tidak disentuh</div>
                <table class="tabel-data">
                    <thead><tr><th>Data</th><th class="angka">Dicek</th><th class="angka">Baru</th><th class="angka">Berubah</th><th class="angka">Dihapus</th><th class="angka">Sama</th></tr></thead>
                    <tbody>
                    @foreach ($sync as $s)
                        <tr data-ds="{{ $s->dataset }}">
                            <td><span class="lencana-ds">{{ $s->dataset }}</span></td>
                            <td class="angka">{{ $s->baris_dicek }}</td>
                            <td class="angka">{{ $s->baris_baru }}</td>
                            <td class="angka">{{ $s->baris_berubah }}</td>
                            <td class="angka">{{ $s->baris_dihapus }}</td>
                            <td class="angka">{{ $s->baris_sama }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @foreach ($sync as $s)
                    @foreach (json_decode($s->detail ?? '[]', true) as $d)
                        <div class="mt-2 text-xs text-ink-3"><b class="text-ink-2">{{ $s->dataset }}</b> · {{ $d['jenis'] ?? 'berubah' }} · {{ $d['baris'] ?? '' }}
                            @if (($d['jenis'] ?? '') === 'berubah') ({{ $d['kolom'] }}: {{ F::angka($d['lama'], 2) }} → {{ F::angka($d['baru'], 2) }}) @endif</div>
                    @endforeach
                @endforeach
            @else
                <div class="kartu-ket">Belum pernah dijalankan. Jalankan <code>python etl/sync.py</code>.</div>
            @endif
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
        @php
            $modeLog = $integrasi['replikasi']->last()?->mode;
        @endphp
        <div class="kartu overflow-x-auto">
            <div class="kartu-judul">Replikasi master → replika</div>
            <div class="mt-2 rounded-xl bg-surface-2 p-2.5 text-[12px] leading-relaxed text-ink-2">
                <b class="text-ink">Replikasi lewat script</b> · <code>etl/replicate.py</code> menyalin perubahan schema <code>kehutanan_master</code>
                ke <code>kehutanan_dashboard</code> (Postgres/Supabase). Website hanya membaca schema replika.
            </div>
            <div class="kartu-ket mt-2">Pemeriksaan terakhir: {{ $integrasi['replikasi']->last()?->waktu }} ({{ $modeLog }}) · checksum sama = replika identik dengan master · + disisipkan, ~ diperbarui, − dihapus</div>
            <table class="tabel-data">
                <thead><tr><th>Tabel</th><th class="angka">Baris</th><th class="angka whitespace-nowrap">+ / ~ / −</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($integrasi['replikasi'] as $r)
                    <tr>
                        <td class="font-mono text-[11.5px] text-ink">{{ $r->tabel }}</td>
                        <td class="angka">{{ $r->baris_master }}</td>
                        <td class="angka whitespace-nowrap">{{ "$r->baris_disisipkan / $r->baris_diperbarui / $r->baris_dihapus" }}</td>
                        <td><span class="{{ $r->status === 'SINKRON' ? 'pil-ok' : 'pil-cek' }}">{{ $r->status === 'SINKRON' ? '✓' : '!' }} {{ $r->status }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="kartu overflow-x-auto xl:col-span-2">
            <div class="kartu-judul">Log ETL terakhir</div>
            <div class="kartu-ket">Run {{ $integrasi['etl']->first()?->run_id }} · cek total = jumlah semua provinsi dibandingkan dengan baris total nasional di file sumber</div>
            <table class="tabel-data">
                <thead><tr><th>Tahap</th><th>Data</th><th class="angka">Masuk</th><th class="angka">Dimuat</th><th class="angka">Dibuang</th><th>Status</th><th>Catatan</th></tr></thead>
                <tbody>
                @foreach ($integrasi['etl'] as $l)
                    <tr @if (in_array($l->dataset, ['D1', 'D2', 'D3', 'D4', 'D5'])) data-ds="{{ $l->dataset }}" @endif>
                        <td>{{ $l->tahap }}</td>
                        <td>
                            @if (in_array($l->dataset, ['D1', 'D2', 'D3', 'D4', 'D5']))
                                <span class="lencana-ds">{{ $l->dataset }}</span>
                            @else
                                <span class="font-semibold text-ink">{{ $l->dataset }}</span>
                            @endif
                        </td>
                        <td class="angka">{{ $l->baris_masuk }}</td>
                        <td class="angka">{{ $l->baris_dimuat }}</td>
                        <td class="angka">{{ $l->baris_dibuang }}</td>
                        <td><span class="{{ $l->status === 'OK' ? 'pil-ok' : 'pil-cek' }}">{{ $l->status === 'OK' ? '✓' : '!' }} {{ $l->status }}</span></td>
                        <td class="max-w-xl text-xs text-ink-3">{{ $l->pesan }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</details>
