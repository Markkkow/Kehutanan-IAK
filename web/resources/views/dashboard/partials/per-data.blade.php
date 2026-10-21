<x-judul-panel judul="Analisis per data"
               sub="Satu baris per data: kiri tren nasional, kanan sebaran per provinsi." />

{{-- Urutan D1 → D5.
     Dua kolom = satu baris per data. items-start: kartu tidak ikut memanjang saat tabel kartu sebelahnya dibuka. --}}
<div class="grid items-start gap-3 md:grid-cols-2">
    @include('dashboard.partials.kelas-lahan')
    @include('dashboard.partials.tutupan')
    @include('dashboard.partials.karhutla')
    @include('dashboard.partials.kayu')
    @include('dashboard.partials.deforestasi')
    @include('dashboard.partials.catatan-data')
</div>
