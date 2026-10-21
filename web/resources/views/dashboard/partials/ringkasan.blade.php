@use('App\Support\Format', 'F')

@php
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

{{-- Tren gabungan 5 data selebar konten; tiap panel memakai warna khas datanya --}}
<div class="mt-3 kartu">
    <div class="flex items-center gap-2">
        <span class="grid size-8 place-items-center rounded-lg bg-brand-lembut text-brand-teks"><x-ikon nama="grafik" class="size-4" /></span>
        <div class="kartu-judul">Tren gabungan, indeks 2018 = 100</div>
    </div>
    <div class="kartu-ket mt-2">Satuan tiap data berbeda, sehingga semuanya diubah ke indeks (nilai tahun itu ÷ nilai 2018 × 100).</div>
    {{-- Layar lebar: 3 panel di baris atas, 2 panel lebih lebar di baris bawah, agar seluruh lebar terisi tanpa ruang kosong --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        @foreach ($panelIndeks as [$judul, $kunci, $ikon, $kodePanel])
            <div data-ds="{{ $kodePanel }}" @class(['subkartu-ds', 'lg:col-span-2' => $loop->index < 3, 'lg:col-span-3' => $loop->index >= 3, 'sm:col-span-2' => $loop->last])>
                <div class="flex items-center gap-1.5 text-[12.5px] font-semibold text-ink">
                    <span class="ikon-ds size-6 rounded-lg"><x-ikon :nama="$ikon" class="size-3.5" /></span>
                    <span>{{ $judul }}</span>
                </div>
                <div class="mt-1.5 h-36"><canvas id="indeks-{{ $kunci }}" aria-label="Indeks {{ $judul }}"></canvas></div>
            </div>
        @endforeach
    </div>
    <details class="lihat-tabel" data-tabel-indeks>
        <summary>Lihat tabel data</summary>
        <div class="isi"></div>
    </details>
</div>
