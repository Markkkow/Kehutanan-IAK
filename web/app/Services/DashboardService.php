<?php

namespace App\Services;

use App\Models\Deforestasi;
use App\Models\EtlLog;
use App\Models\Karhutla;
use App\Models\ProduksiKayu;
use App\Models\Provinsi;
use App\Models\ReplicationLog;
use App\Models\SyncLog;
use App\Models\TutupanKelas;
use App\Models\TutupanLahan;
use Illuminate\Support\Collection;

/**
 * Menyiapkan data siap-tampil untuk dashboard. Semua angka dibaca dari kehutanan_dashboard (replika).
 */
class DashboardService
{
    private const TAHUN_AWAL = 2018;

    /** Karhutla tahun berjalan: datanya baru s.d. Juli, jadi hanya untuk peta & peringkat, tidak untuk tren. */
    private const KARHUTLA_PARSIAL = 2026;

    private const LABEL_PARSIAL = '2026 (Jan–Jul)';


    private ?Collection $provinsi = null;

    private function provinsi(): Collection
    {
        return $this->provinsi ??= Provinsi::orderBy('kode_bps')->get()->keyBy('kode_bps');
    }

    private function nama(string $kode): string
    {
        return $this->provinsi()[$kode]->nama ?? $kode;
    }

    /** Kalimantan Utara digabung ke Kalimantan Timur agar setara dengan D2 dan D5 (lihat catatan sumber di data/raw/SUMBER_DATA.md). */
    private function kodeGabungan(string $kode): string
    {
        return $kode === '65' ? '64' : $kode;
    }

    private function persen(?float $baru, ?float $lama): ?float
    {
        return $lama ? ($baru - $lama) / $lama * 100 : null;
    }

    // ------------------------------------------------------------------ Ringkasan

    public function kpi(): array
    {
        $primer = $this->deretJenis('primer');
        $tutupanTahun = TutupanLahan::max('tahun');
        $tutupan = TutupanLahan::where('tahun', $tutupanTahun)->selectRaw('SUM(hutan_ha) h, SUM(total_ha) t')->first();
        $tutupanAwal = TutupanLahan::where('tahun', self::TAHUN_AWAL)->sum('hutan_ha');

        // kartu ringkasan hanya memakai tahun penuh; 2026 (parsial) tetap tampil di grafik D3
        $karhutla = Karhutla::where('tahun', '<>', self::KARHUTLA_PARSIAL)
            ->selectRaw('tahun, SUM(luas_ha) luas')->groupBy('tahun')->orderBy('tahun')->pluck('luas', 'tahun');
        $kayu = ProduksiKayu::selectRaw('tahun, SUM(produksi_m3) m3')->groupBy('tahun')->pluck('m3', 'tahun');
        $defor = Deforestasi::selectRaw('periode, SUM(total_ha) total')->groupBy('periode')->orderBy('periode')->pluck('total', 'periode');

        $deret = fn (Collection $c) => $c->map(fn ($v) => (float) $v)->all();

        return [
            // deret nasional per tahun untuk sparkline di kartu ringkasan
            'deret' => [
                'primer' => $deret($primer),
                'tutupan' => $deret(TutupanLahan::selectRaw('tahun, SUM(hutan_ha) v')->groupBy('tahun')->orderBy('tahun')->pluck('v', 'tahun')),
                'deforestasi' => $deret($defor),
                'karhutla' => $deret($karhutla),
                'kayu' => $deret($kayu),
            ],
            'primer' => [
                'tahun' => $primer->keys()->last(), 'nilai' => (float) $primer->last(),
                'perubahan' => $this->persen($primer->last(), $primer[self::TAHUN_AWAL] ?? null),
            ],
            'tutupan' => [
                'tahun' => $tutupanTahun, 'nilai' => (float) $tutupan->h, 'persen' => $tutupan->h / $tutupan->t * 100,
                'perubahan' => $this->persen($tutupan->h, $tutupanAwal),
            ],
            'karhutla' => [
                'tahun' => $karhutla->keys()->last(), 'nilai' => (float) $karhutla->last(),
                'perubahan' => $this->persen($karhutla->last(), $karhutla[self::TAHUN_AWAL] ?? null),
            ],
            'kayu' => [
                'tahun' => $kayu->keys()->last(), 'nilai' => (float) $kayu->last(),
                'perubahan' => $this->persen($kayu->last(), $kayu[self::TAHUN_AWAL] ?? null),
            ],
            'deforestasi' => [
                'periode' => $defor->keys()->last(), 'nilai' => (float) $defor->last(),
                'perubahan' => $this->persen($defor->last(), $defor['2018-2019'] ?? null),
            ],
        ];
    }

    // ------------------------------------------------------------------ D1

    /** Luas nasional per tahun untuk satu jenis hutan (primer / sekunder / tanaman). */
    private function deretJenis(string $jenis): Collection
    {
        return TutupanKelas::where('jenis_hutan', $jenis)->selectRaw('tahun, SUM(total_ha) v')
            ->groupBy('tahun')->orderBy('tahun')->pluck('v', 'tahun')->map(fn ($v) => (float) $v);
    }

    public function tutupanKelas(): array
    {
        $tahun = TutupanKelas::distinct()->orderBy('tahun')->pluck('tahun');
        [$awal, $akhir] = [$tahun->first(), $tahun->last()];

        $jenis = collect(['primer' => 'Hutan primer', 'sekunder' => 'Hutan sekunder', 'tanaman' => 'Hutan tanaman'])
            ->map(fn ($label, $k) => ['label' => $label, 'nilai' => $this->deretJenis($k)]);
        // perubahan kumulatif terhadap tahun awal: semua garis mulai dari 0 agar arah perubahannya langsung terbaca
        $kumulatif = $jenis->map(fn ($j) => ['label' => $j['label'],
            'data' => $tahun->map(fn ($t) => $j['nilai'][$t] - $j['nilai'][$awal])->values()]);

        $baris = TutupanKelas::whereIn('tahun', [$awal, $akhir])->get()->groupBy('kode_kelas');
        $perKelas = $baris->map(function ($g) use ($awal, $akhir) {
            $a = $g->firstWhere('tahun', $awal);
            $b = $g->firstWhere('tahun', $akhir);

            return ['kelas' => $b->kelas, 'kelompok' => $b->kelompok, 'awal' => (float) $a->total_ha, 'akhir' => (float) $b->total_ha,
                'selisih' => (float) ($b->total_ha - $a->total_ha)];
        })->values();

        $hutan = fn ($t) => (float) TutupanKelas::where('kelompok', 'hutan')->where('tahun', $t)->sum('total_ha');
        $perkebunan = $perKelas->firstWhere('kelas', 'Perkebunan');
        $kawasanAkhir = TutupanKelas::where('tahun', $akhir)->selectRaw("SUM(kawasan_ha) semua, SUM(CASE WHEN kelompok = 'hutan' THEN kawasan_ha END) hutan")->first();
        $p = $jenis['primer']['nilai'];

        return [
            'tahun' => $tahun, 'awal' => $awal, 'akhir' => $akhir,
            'kumulatif' => $kumulatif->values(),
            'luas' => $jenis->map(fn ($j) => $j['nilai']->values()),
            // 10 perubahan terbesar (naik atau turun), diurutkan dari kenaikan terbesar
            'perubahan' => $perKelas->sortByDesc(fn ($k) => abs($k['selisih']))->take(10)->sortByDesc('selisih')->values(),
            'hutan_awal' => $hutan($awal), 'hutan_akhir' => $hutan($akhir),
            'selisih' => $jenis->map(fn ($j) => $j['nilai'][$akhir] - $j['nilai'][$awal]),
            'primer_awal' => $p[$awal], 'primer_akhir' => $p[$akhir],
            'primer_sebelum_akhir' => $p[$akhir - 1] ?? null,
            'perkebunan' => $perkebunan,
            'hutan_dalam_kawasan_persen' => $kawasanAkhir->hutan / $kawasanAkhir->semua * 100,
        ];
    }

    // ------------------------------------------------------------------ D2

    public function tutupanLahan(): array
    {
        $nasional = TutupanLahan::selectRaw('tahun, SUM(hutan_ha) hutan, SUM(total_ha) total')
            ->groupBy('tahun')->orderBy('tahun')->get();
        $akhir = TutupanLahan::max('tahun');

        $awal = TutupanLahan::where('tahun', self::TAHUN_AWAL)->pluck('hutan_ha', 'kode_bps');
        $perubahan = TutupanLahan::where('tahun', $akhir)->get()
            ->map(fn ($r) => ['nama' => $this->nama($r->kode_bps), 'selisih' => $r->hutan_ha - ($awal[$r->kode_bps] ?? 0),
                'persen' => (float) $r->persen_hutan])
            ->sortBy('selisih')->values();

        return [
            'tahun_akhir' => $akhir,
            // Rentang tahun lengkap; tahun yang tidak ada datanya muncul sebagai celah (garis putus-putus)
            'tahun' => range(self::TAHUN_AWAL, $akhir),
            'nasional' => $nasional->map(fn ($r) => ['tahun' => $r->tahun, 'hutan' => (float) $r->hutan,
                'persen' => $r->hutan / $r->total * 100]),
            'perubahan' => $perubahan->take(8)->concat($perubahan->reverse()->take(5)->reverse())->values(),
            'jumlah_turun' => $perubahan->where('selisih', '<', 0)->count(),
            'jumlah_provinsi' => $perubahan->count(),
        ];
    }

    // ------------------------------------------------------------------ D5

    public function deforestasi(): array
    {
        $nasional = Deforestasi::selectRaw('periode, SUM(dalam_kawasan_ha) dalam, SUM(luar_kawasan_ha) luar, SUM(total_ha) total')
            ->groupBy('periode')->orderBy('periode')->get();

        $provinsi = Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)
            ->selectRaw('kode_bps, SUM(total_ha) total, SUM(dalam_kawasan_ha) dalam')
            ->groupBy('kode_bps')->orderByDesc('total')->get();
        $totalSemua = $provinsi->sum('total');

        $puncak = $nasional->sortByDesc('total')->first();

        return [
            'nasional' => $nasional->map(fn ($r) => ['periode' => $r->periode, 'dalam' => (float) $r->dalam,
                'luar' => (float) $r->luar, 'total' => (float) $r->total]),
            'top' => $provinsi->take(10)->map(fn ($r) => ['nama' => $this->nama($r->kode_bps), 'total' => (float) $r->total])->values(),
            'top5_persen' => $provinsi->take(5)->sum('total') / $totalSemua * 100,
            'dalam_persen' => $provinsi->sum('dalam') / $totalSemua * 100,
            'puncak' => $puncak->periode,
            'reforestasi' => Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)->where('total_ha', '<', 0)
                ->count(),
        ];
    }

    // ------------------------------------------------------------------ D3 & D4 (pola sama: nasional + matriks provinsi x tahun)

    private function matriks(Collection $rows, string $kolom): array
    {
        $tahun = $rows->pluck('tahun')->unique()->sort()->values();
        $nasional = $rows->groupBy('tahun')->map(fn ($g) => (float) $g->sum($kolom))->sortKeys();
        $perProvinsi = $rows->groupBy('kode_bps')->map(fn ($g, $kode) => [
            'nama' => $this->nama($kode),
            'nilai' => $tahun->map(fn ($t) => (float) ($g->firstWhere('tahun', $t)?->{$kolom} ?? 0))->values(),
        ])->values();

        return ['tahun' => $tahun, 'nasional' => $nasional, 'provinsi' => $perProvinsi];
    }

    public function karhutla(): array
    {
        $m = $this->matriks(Karhutla::all(), 'luas_ha');
        $parsial = $m['nasional'][self::KARHUTLA_PARSIAL] ?? null;
        $m['nasional'] = $m['nasional']->except(self::KARHUTLA_PARSIAL); // puncak & sorotan dihitung dari tahun penuh saja
        $puncak = $m['nasional']->sortDesc()->keys()->first();
        $rows2019 = Karhutla::where('tahun', $puncak)->get();
        $wilayah = $rows2019->groupBy(fn ($r) => $this->provinsi()[$r->kode_bps]->wilayah)
            ->map(fn ($g) => $g->sum('luas_ha'))->sortDesc();

        return $m + [
            'puncak' => $puncak,
            'label_tahun' => [self::KARHUTLA_PARSIAL => self::LABEL_PARSIAL],
            'parsial' => $parsial === null ? null : ['label' => self::LABEL_PARSIAL, 'nilai' => $parsial],
            'puncak_kedua' => $m['nasional']->sortDesc()->keys()->get(1),
            'sorot' => $m['nasional']->sortDesc()->keys()->take(2)->values(),
            'wilayah_puncak' => $wilayah->keys()->take(2)->values(),
            'wilayah_puncak_persen' => $wilayah->take(2)->sum() / $wilayah->sum() * 100,
        ];
    }

    public function produksiKayu(): array
    {
        $m = $this->matriks(ProduksiKayu::all(), 'produksi_m3');
        $akhir = $m['tahun']->last();
        $rows = ProduksiKayu::where('tahun', $akhir)->orderByDesc('produksi_m3')->get();

        return $m + [
            'puncak' => $m['nasional']->sortDesc()->keys()->first(),
            'teratas' => $this->nama($rows->first()->kode_bps),
            'teratas_persen' => $rows->first()->produksi_m3 / $rows->sum('produksi_m3') * 100,
            'top3_persen' => $rows->take(3)->sum('produksi_m3') / $rows->sum('produksi_m3') * 100,
        ];
    }

    // ------------------------------------------------------------------ Gabungan

    /** G1: tren gabungan dalam indeks (2018 = 100) supaya satuan berbeda bisa dibandingkan. */
    public function indeksGabungan(): array
    {
        $deret = [
            'Hutan primer (D1)' => $this->deretJenis('primer'),
            'Tutupan hutan (D2)' => TutupanLahan::selectRaw('tahun, SUM(hutan_ha) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Karhutla (D3)' => Karhutla::selectRaw('tahun, SUM(luas_ha) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Produksi kayu (D4)' => ProduksiKayu::selectRaw('tahun, SUM(produksi_m3) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Deforestasi netto (D5)' => Deforestasi::selectRaw('tahun_akhir tahun, SUM(total_ha) v')->groupBy('tahun_akhir')->pluck('v', 'tahun'),
        ];
        $tahun = range(self::TAHUN_AWAL, max(array_map(fn ($d) => $d->keys()->max(), $deret)));

        $hasil = [];
        foreach ($deret as $label => $d) {
            $dasar = $d[self::TAHUN_AWAL] ?? null;
            $hasil[$label] = array_map(fn ($t) => isset($d[$t]) && $dasar ? round($d[$t] / $dasar * 100, 1) : null, $tahun);
        }

        return ['tahun' => $tahun, 'deret' => $hasil, 'label_tahun' => [self::KARHUTLA_PARSIAL => self::LABEL_PARSIAL]];
    }

    /**
     * G2: kebakaran (D3) vs hutan hilang (D5), nasional 2018-2022 (irisan kedua data). Keduanya dalam hektar.
     * D5 berupa periode dua tahun; nilainya dipasangkan dengan tahun akhir periode (2018-2019 -> 2019).
     */
    public function kebakaranVsDeforestasi(): array
    {
        $tahun = range(self::TAHUN_AWAL, 2022);
        $bakar = Karhutla::whereIn('tahun', $tahun)->selectRaw('tahun, SUM(luas_ha) v')->groupBy('tahun')->pluck('v', 'tahun');
        $hilang = Deforestasi::whereIn('tahun_akhir', $tahun)->selectRaw('tahun_akhir, SUM(total_ha) v')->groupBy('tahun_akhir')->pluck('v', 'tahun_akhir');
        $x = collect($tahun)->map(fn ($t) => (float) $bakar[$t]);
        $y = collect($tahun)->map(fn ($t) => (float) $hilang[$t]);
        $puncak = $x->search($x->max());

        return [
            'tahun' => $tahun,
            'periode' => collect($tahun)->map(fn ($t) => ($t - 1).'–'.$t),
            'karhutla' => $x, 'deforestasi' => $y,
            'r' => $this->korelasi($x, $y),
            'puncak' => ['tahun' => $tahun[$puncak], 'karhutla' => $x[$puncak], 'deforestasi' => $y[$puncak]],
            'setelah' => ['karhutla' => $x->slice($puncak + 1)->avg(), 'deforestasi' => $y->slice($puncak + 1)->avg()],
        ];
    }

    /**
     * Validasi silang 2018-2022 (ditampilkan di Catatan data) (irisan ketiga data). Kalau hutan hilang (D5), tutupan hutan (D2) seharusnya turun;
     * rincian per jenis hutan (D1) dipakai untuk melihat dari mana perubahan D2 berasal.
     */
    public function validasiSilang(): array
    {
        [$awal, $akhir] = [self::TAHUN_AWAL, 2022];
        $ubah = fn (Collection $d) => (float) ($d[$akhir] - $d[$awal]);
        $d2 = TutupanLahan::whereIn('tahun', [$awal, $akhir])->selectRaw('tahun, SUM(hutan_ha) v')->groupBy('tahun')->pluck('v', 'tahun');
        $hilang = (float) Deforestasi::where('tahun_awal', '>=', $awal)->where('tahun_akhir', '<=', $akhir)->sum('total_ha');
        $total = $ubah($d2);

        return [
            'periode' => "{$awal}–{$akhir}",
            'hilang_d5' => $hilang,
            'total_d2' => $total,
            'selisih' => $total + $hilang,
            'jenis_d1' => [
                'sekunder' => $ubah($this->deretJenis('sekunder')),
                'tanaman' => $ubah($this->deretJenis('tanaman')),
                'primer' => $ubah($this->deretJenis('primer')),
            ],
        ];
    }

    /** G3: tiap provinsi = satu titik. Karhutla vs deforestasi, ukuran titik = produksi kayu (2018-2022). */
    public function benangMerahProvinsi(): array
    {
        $akhir = 2022;
        $kumpul = fn (Collection $rows, string $kolom) => $rows
            ->groupBy(fn ($r) => $this->kodeGabungan($r->kode_bps))
            ->map(fn ($g) => (float) $g->sum($kolom));

        $karhutla = $kumpul(Karhutla::whereBetween('tahun', [self::TAHUN_AWAL, $akhir])->get(), 'luas_ha');
        $kayu = $kumpul(ProduksiKayu::whereBetween('tahun', [self::TAHUN_AWAL, $akhir])->get(), 'produksi_m3');
        $defor = $kumpul(Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)->where('tahun_akhir', '<=', $akhir)->get(), 'total_ha');

        // Maksimal 3 warna agar tetap terbedakan (termasuk buta warna): dua pulau utama + sisanya
        $kelompok = fn ($wilayah) => in_array($wilayah, ['Sumatera', 'Kalimantan']) ? $wilayah : 'Wilayah lain';

        $titik = $defor->map(fn ($d, $kode) => [
            'nama' => $kode === '64' ? 'Kalimantan Timur + Utara' : $this->nama($kode),
            'wilayah' => $kelompok($this->provinsi()[$kode]->wilayah),
            'karhutla' => $karhutla[$kode] ?? 0,
            'deforestasi' => $d,
            'kayu' => $kayu[$kode] ?? 0,
        ])->values();

        return [
            'periode' => self::TAHUN_AWAL.'-'.$akhir,
            'titik' => $titik,
            'r_karhutla_defor' => $this->korelasi($titik->pluck('karhutla'), $titik->pluck('deforestasi')),
            'r_kayu_defor' => $this->korelasi($titik->pluck('kayu'), $titik->pluck('deforestasi')),
            'wilayah' => ['Sumatera', 'Kalimantan', 'Wilayah lain'],
        ];
    }

    private function korelasi(Collection $x, Collection $y): ?float
    {
        $n = $x->count();
        $mx = $x->avg();
        $my = $y->avg();
        $sxy = $sxx = $syy = 0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $mx;
            $dy = $y[$i] - $my;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }

        return $sxx && $syy ? $sxy / sqrt($sxx * $syy) : null;
    }

    // ------------------------------------------------------------------ Peta sebaran

    /** Nilai per provinsi untuk setiap dataset & tahun, plus posisi petak. Dipakai peta sebaran (dropdown dataset/tahun). */
    public function sebaran(): array
    {
        $perTahun = function (Collection $rows, string $kolomTahun, string $kolomNilai) {
            return $rows->groupBy($kolomTahun)->sortKeys()
                ->map(fn ($g) => $g->mapWithKeys(fn ($r) => [$r->kode_bps => round((float) $r->{$kolomNilai}, 2)]));
        };

        $dataset = [
            'D2' => [
                'label' => 'Tutupan hutan (% daratan)', 'satuan' => '%', 'skala' => 'sekuensial', 'kaltaraDigabung' => true,
                'nilai' => $perTahun(TutupanLahan::all(), 'tahun', 'persen_hutan'),
                'catatan' => 'Persen hutan sesuai kolom % di tabel BPS (Kalimantan Timur 2021 ke atas dihitung ulang karena digabung dengan Kalimantan Utara).',
            ],
            'D5' => [
                'label' => 'Deforestasi netto', 'satuan' => 'ha/th', 'skala' => 'divergen', 'kaltaraDigabung' => true,
                'nilai' => $perTahun(Deforestasi::all(), 'periode', 'total_ha'),
                'catatan' => 'Merah karat = hutan berkurang, hijau = reforestasi lebih besar (netto negatif).',
            ],
            'D3' => [
                'label' => 'Luas kebakaran hutan & lahan', 'satuan' => 'ha', 'skala' => 'sekuensial', 'kaltaraDigabung' => false,
                'nilai' => $perTahun(Karhutla::all(), 'tahun', 'luas_ha')->keyBy(fn ($v, $t) => $t == self::KARHUTLA_PARSIAL ? self::LABEL_PARSIAL : $t),
                'catatan' => 'Luas indikatif terbakar menurut SiPongi. Pilihan 2026 hanya berisi data Januari–Juli, jadi belum bisa dibandingkan dengan tahun penuh.',
            ],
            'D4' => [
                'label' => 'Produksi kayu bulat', 'satuan' => 'm³', 'skala' => 'sekuensial', 'kaltaraDigabung' => false,
                'nilai' => $perTahun(ProduksiKayu::all(), 'tahun', 'produksi_m3'),
                'catatan' => 'Sumber BPS (file JSON).',
            ],
        ];

        return [
            'provinsi' => $this->provinsi()->map(fn ($p) => ['kode' => $p->kode_bps, 'nama' => $p->nama, 'wilayah' => $p->wilayah])->values(),
            'dataset' => $dataset,
        ];
    }

    // ------------------------------------------------------------------ Status integrasi (bukti ETL, replikasi, sinkronisasi)

    public function statusIntegrasi(): array
    {
        $runTerakhir = EtlLog::max('run_id');
        // hanya pemeriksaan terakhir (semua tabel dicatat dalam satu menit yang sama), satu baris per tabel
        $terakhir = ReplicationLog::max('waktu');
        $replikasi = ReplicationLog::whereIn('id', ReplicationLog::selectRaw('MAX(id)')
            ->where('waktu', '>=', now()->parse($terakhir)->subMinute())->groupBy('tabel'))->orderBy('id')->get();

        return [
            'sumber' => [
                ['kode' => 'D1', 'nama' => 'Tutupan lahan menurut kelas (nasional)', 'penerbit' => 'BPS / KLHK', 'format' => 'XLS', 'cara' => 'Unduh file'],
                ['kode' => 'D2', 'nama' => 'Tutupan lahan hutan & non-hutan', 'penerbit' => 'BPS / KLHK', 'format' => 'CSV', 'cara' => 'Unduh file'],
                ['kode' => 'D3', 'nama' => 'Luas kebakaran hutan & lahan', 'penerbit' => 'SiPongi KLHK', 'format' => 'XLSX', 'cara' => 'Unduh file'],
                ['kode' => 'D4', 'nama' => 'Produksi kayu bulat', 'penerbit' => 'BPS', 'format' => 'JSON', 'cara' => 'Unduh file (per tahun)'],
                ['kode' => 'D5', 'nama' => 'Deforestasi netto', 'penerbit' => 'BPS / KLHK', 'format' => 'CSV', 'cara' => 'Unduh file'],
            ],
            'etl' => EtlLog::where('run_id', $runTerakhir)->orderBy('id')->get(),
            'replikasi' => $replikasi,
            // putaran sinkronisasi terakhir: satu baris per data (D1-D5), dicatat dengan waktu yang sama
            'sync' => SyncLog::where('waktu', SyncLog::max('waktu'))->orderBy('dataset')->get(),
        ];
    }
}
