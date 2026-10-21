@php
    $ikonPeta = ['D2' => 'daun', 'D5' => 'kapak', 'D3' => 'api', 'D4' => 'kayu'];
@endphp

<div id="wadahSebaran" data-ds="D2">
    <x-judul-panel judul="Peta sebaran per provinsi"
                   sub="Warna tiap provinsi menunjukkan nilai data yang dipilih: makin pekat, makin besar.">
        <div class="flex flex-wrap items-end gap-3">
            <div hidden>
                <label for="sebaranData" class="label-kontrol">Data</label>
                <select id="sebaranData" class="pilih min-w-64">
                    @foreach ($sebaran['dataset'] as $kode => $d)
                        <option value="{{ $kode }}">{{ $d['label'] }} ({{ $kode }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sebaranTahun" class="label-kontrol">Tahun / periode</label>
                <select id="sebaranTahun" class="pilih min-w-32"></select>
            </div>
        </div>
    </x-judul-panel>

    {{-- Pilihan data peta: warna kartu peta ikut berganti sesuai data terpilih --}}
    <div class="mb-3 flex flex-wrap items-center gap-2" role="group" aria-label="Pilih cepat dataset peta">
        @foreach ($sebaran['dataset'] as $kode => $d)
            <button type="button" data-pilih-peta="{{ $kode }}" data-ds="{{ $kode }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="pil-peta-ds">
                <x-ikon :nama="$ikonPeta[$kode] ?? 'peta'" class="size-3.5" />
                <span class="font-bold">{{ $kode }}</span>
                <span>{{ $d['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div id="kartuPetaUtama" data-ds="D2" class="kartu">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <span id="ikonPetaAktif" class="ikon-ds size-8"><x-ikon nama="peta" class="size-4" /></span>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span id="lencanaPetaKode" class="lencana-ds">D2</span>
                            <div class="kartu-judul" id="sebaranJudul"></div>
                        </div>
                        <div class="kartu-ket mb-0" id="sebaranCatatan"></div>
                    </div>
                </div>
                <div id="sebaranLegenda" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-xl border border-garis bg-surface-2 px-3 py-1.5 text-[11px] text-ink-2" aria-label="Legenda warna"></div>
            </div>

            <div id="peta" class="mt-3 min-h-48" role="group" aria-label="Peta provinsi Indonesia"></div>
            <p class="catatan-kaki">Kaltara bergaris: angkanya digabung ke Kalimantan Timur. D5 memang demikian dari sumbernya; D2 dari BPS baru dipisah sejak 2021, sehingga ETL tetap menggabungkannya agar deret waktunya konsisten.</p>
            <p class="catatan-kaki !mt-1">Batas wilayah: BAKOSURTANAL/Dukcapil via peta-indonesia-geojson (MIT).</p>
            <details class="lihat-tabel" data-tabel-peta>
                <summary>Lihat tabel data</summary>
                <div class="isi"></div>
            </details>
        </div>

        <div class="grid content-start gap-4">
            <div id="kartuDetailProvinsi" data-ds="D2" class="kartu">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-[11px] font-semibold uppercase" style="color: var(--ds-teks)">Provinsi terpilih</div>
                    <span id="lencanaDetailKode" class="lencana-ds">D2</span>
                </div>
                <div class="mt-1 text-lg font-semibold" id="detailNama">—</div>
                <div class="text-xs text-ink-3" id="detailWilayah"></div>
                <div class="mt-3 flex items-baseline gap-2 rounded-xl border border-[var(--ds-garis)] bg-surface-2 px-3 py-2.5">
                    <span class="text-[28px] leading-none font-bold tabular-nums" id="detailNilai">—</span>
                    <span class="text-sm text-ink-3" id="detailSatuan"></span>
                </div>
                <div class="mt-2 text-xs text-ink-2" id="detailPeringkat"></div>
                <div class="mt-4 h-36"><canvas id="chartDetailProvinsi" aria-label="Tren provinsi terpilih"></canvas></div>
            </div>
            <div id="kartuTop5Provinsi" data-ds="D2" class="kartu">
                <div class="flex items-center justify-between gap-2">
                    <div class="kartu-judul">5 provinsi tertinggi</div>
                    <span id="lencanaTop5Kode" class="lencana-ds">D2</span>
                </div>
                <div class="kartu-ket" id="top5Ket"></div>
                <ol id="top5" class="grid gap-2.5"></ol>
            </div>
        </div>
    </div>
</div>
