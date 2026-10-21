@props(['id', 'judul', 'ket' => null, 'tinggi' => 'h-56', 'kode' => null, 'ikon' => null, 'jangkar' => null])

{{--
    Kartu grafik: judul, keterangan, kanvas Chart.js, dan tabel data pendamping (diisi otomatis oleh dashboard.js).
    Slot opsional: $aksi (kontrol di kanan judul), $ringkas (satu kalimat temuan di bawah grafik),
    $info (penjelasan lengkap, dibuka lewat tombol ⓘ).
    Atribut data-ds memberi warna khas tiap data (D1–D5, G2, G3) pada garis atas kartu, ikon, dan lencana kode.
--}}
<figure @if ($jangkar) id="{{ $jangkar }}" @endif @if ($kode) data-ds="{{ $kode }}" @endif {{ $attributes->merge(['class' => 'kartu m-0 flex scroll-mt-28 flex-col']) }}>
    <figcaption class="relative z-10 flex items-start gap-2.5">
        @if ($ikon)
            <span class="ikon-ds size-8"><x-ikon :nama="$ikon" class="size-4" /></span>
        @endif
        <div class="min-w-0 flex-1">
            <div class="kartu-judul">@if ($kode)<span class="lencana-ds mr-1.5 align-[1px]">{{ $kode }}</span>@endif{{ $judul }}</div>
            @if ($ket)
                <div class="kartu-ket">{{ $ket }}</div>
            @endif
        </div>
        {{ $aksi ?? '' }}
        @isset($info)
            <button type="button" class="tombol-info" popovertarget="info-{{ $id }}" aria-label="Penjelasan: {{ $judul }}" title="Kenapa begini?">i</button>
            <div popover id="info-{{ $id }}" @if ($kode) data-ds="{{ $kode }}" @endif class="popover-info">
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div class="kartu-judul">@if ($kode)<span class="lencana-ds mr-1.5 align-[1px]">{{ $kode }}</span>@endif{{ $judul }}</div>
                    <button type="button" class="text-ink-3 hover:text-ink" popovertarget="info-{{ $id }}" popovertargetaction="hide" aria-label="Tutup">✕</button>
                </div>
                {{ $info }}
            </div>
        @endisset
    </figcaption>
    {{ $slot }}
    <div class="relative z-10 {{ $tinggi }}">
        <canvas id="{{ $id }}" role="img" aria-label="{{ $judul }}"></canvas>
    </div>
    @isset($ringkas)
        <p class="ringkas-ds relative z-10">{{ $ringkas }}</p>
    @endisset
    <details class="lihat-tabel relative z-10 mt-auto pt-2" data-grafik="{{ $id }}">
        <summary>Lihat tabel data</summary>
        <div class="isi"></div>
    </details>
</figure>
