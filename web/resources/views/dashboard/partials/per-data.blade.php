<x-judul-panel judul="Analisis per data"
               sub="Satu layar per data. Gulir ke bawah: latar berganti mengikuti data yang sedang dibahas." />

@php
    $slides = [
        ['D1', 'kelas-lahan', 'Hutan primer dan kelas lahan', 'Total hutan naik, tetapi yang bertambah bukan hutan primer.'],
        ['D2', 'tutupan', 'Tutupan hutan', 'Berapa persen daratan yang masih berhutan, dan provinsi mana yang berubah.'],
        ['D3', 'karhutla', 'Kebakaran hutan dan lahan', 'Dua lonjakan besar pada tahun El Niño, sisanya jauh lebih kecil.'],
        ['D4', 'kayu', 'Produksi kayu bulat', 'Naik terus hingga 2023, dan terpusat di sedikit provinsi.'],
        ['D5', 'deforestasi', 'Deforestasi netto', 'Laju hilangnya hutan turun tajam setelah 2019.'],
    ];
@endphp

@foreach ($slides as [$kode, $partial, $judul, $sub])
    <section class="slide" id="slide-{{ strtolower($kode) }}" data-slide="{{ $kode }}" data-ds="{{ $kode }}" aria-label="{{ $judul }}">
        <header class="slide-kepala">
            <span class="tile-tag">{{ $kode }}</span>
            <h2>{{ $judul }}</h2>
            <p>{{ $sub }}</p>
        </header>
        <div class="grid items-start gap-4 md:grid-cols-2">
            @include('dashboard.partials.'.$partial)
        </div>
    </section>
@endforeach

<div class="mt-4 grid gap-3">
    @include('dashboard.partials.catatan-data')
</div>
