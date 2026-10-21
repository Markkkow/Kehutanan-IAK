@use('App\Support\Format', 'F')

@php
    $sel = $kelas['selisih'];
    $primerPersen = ($kelas['primer_akhir'] - $kelas['primer_awal']) / $kelas['primer_awal'] * 100;
    $primerAkhirSaja = $kelas['primer_akhir'] - $kelas['primer_sebelum_akhir'];
    $kebun = $kelas['perkebunan'];
@endphp

<x-grafik id="chartKelasTren" jangkar="kelas-lahan" kode="D1" ikon="pohon" judul="Perubahan luas hutan menurut jenis, sejak {{ $kelas['awal'] }}"
          ket="BPS/KLHK · XLS · ha · nasional · 0 = luas tahun {{ $kelas['awal'] }}">
    <x-slot:ringkas>
        Total hutan naik {{ F::ringkas($kelas['hutan_akhir'] - $kelas['hutan_awal']) }} ha, tetapi <b class="font-semibold text-ink">hutan primer turun
        {{ F::ringkas(abs($sel['primer'])) }} ha</b>. Yang bertambah hutan sekunder (+{{ F::ringkas($sel['sekunder']) }}) dan hutan tanaman (+{{ F::ringkas($sel['tanaman']) }}).
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Dari {{ $kelas['awal'] }} ke {{ $kelas['akhir'] }}, hutan primer berkurang {{ F::ringkas(abs($sel['primer'])) }} ha
                ({{ F::persen($primerPersen, 1, true) }}), sedangkan hutan sekunder bertambah {{ F::ringkas($sel['sekunder']) }} ha dan hutan tanaman
                {{ F::ringkas($sel['tanaman']) }} ha. Penurunan hutan primer hampir seluruhnya terjadi pada {{ $kelas['akhir'] - 1 }} → {{ $kelas['akhir'] }}
                ({{ F::ringkas($primerAkhirSaja) }} ha); sebelumnya nyaris datar.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Menurut keterangan BPS di tabel ini, hutan tanaman adalah hutan hasil budidaya manusia, baik hutan tanaman industri maupun hasil
                reboisasi/penghijauan. Hutan sekunder bisa bertambah ketika lahan bekas tebangan atau semak tumbuh kembali. Turunnya hutan primer
                yang berbarengan dengan naiknya hutan sekunder pada {{ $kelas['akhir'] - 1 }} → {{ $kelas['akhir'] }} menurut kami lebih mungkin berupa
                perpindahan kelas (hutan primer yang terganggu dicatat sebagai sekunder) daripada hutan yang hilang sama sekali. Ini dugaan kami dan
                perlu dicek di buku Rekalkulasi Penutupan Lahan KLHK.
            </x-slot:kenapa>
            <x-slot:lanjut>Total hutan yang sama dilihat per provinsi di D2.</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartKelasPerubahan" kode="D1" ikon="pohon" judul="10 perubahan terbesar menurut kelas lahan, {{ $kelas['awal'] }} → {{ $kelas['akhir'] }}"
          ket="ha · nasional · hijau = kelas hutan, cokelat = kelas non-hutan">
    <x-slot:ringkas>
        Perkebunan bertambah paling besar: +{{ F::ringkas($kebun['selisih']) }} ha ({{ F::ringkas($kebun['awal']) }} → {{ F::ringkas($kebun['akhir']) }} ha),
        sementara semak belukar dan pertanian lahan kering menyusut.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Perkebunan tumbuh {{ F::persen($kebun['selisih'] / $kebun['awal'] * 100, 0) }} dalam {{ $kelas['akhir'] - $kelas['awal'] }} tahun, perubahan
                terbesar di antara 21 kelas. Pada {{ $kelas['akhir'] }}, hanya {{ F::persen($kelas['hutan_dalam_kawasan_persen'], 0) }} daratan kawasan hutan
                yang masih berupa hutan; sisanya semak, kebun, ladang, atau lahan terbuka.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Tabel ini hanya mencatat luas tiap kelas per tahun, bukan perpindahan dari kelas mana ke kelas mana. Jadi asal lahan kebun baru tidak
                bisa dipastikan. Menyusutnya semak belukar, pertanian lahan kering, dan tanah terbuka pada saat yang sama memberi dugaan bahwa kebun
                banyak dibuka di lahan yang sudah bukan hutan (dugaan kami).
            </x-slot:kenapa>
        </x-insight>
    </x-slot:info>
</x-grafik>
