@use('App\Support\Format', 'F')

@php
    $awal = $tutupan['nasional']->first();
    $akhir = $tutupan['nasional']->last();
@endphp

<x-grafik id="chartTutupanTren" jangkar="tutupan" kode="D2" ikon="daun" judul="Tutupan hutan nasional, 2018–{{ $tutupan['tahun_akhir'] }}"
          ket="BPS/KLHK · CSV · ha">
    <x-slot:ringkas>
        Naik dari {{ F::ringkas($awal['hutan']) }} ha ({{ F::persen($awal['persen']) }} daratan) menjadi {{ F::ringkas($akhir['hutan']) }} ha
        ({{ F::persen($akhir['persen']) }}), tapi D1 menunjukkan yang bertambah hutan sekunder dan tanaman, bukan hutan primer.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Tutupan hutan nasional naik dari {{ F::ringkas($awal['hutan']) }} ha ({{ F::persen($awal['persen']) }} daratan) pada 2018 menjadi
                {{ F::ringkas($akhir['hutan']) }} ha ({{ F::persen($akhir['persen']) }}) pada {{ $tutupan['tahun_akhir'] }}.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Data ini hasil <em>rekalkulasi</em> KLHK 2023: semua tahun dihitung ulang dengan metode yang sama, dan hutan tanaman (mis. HTI akasia
                dan eukaliptus) ikut dihitung sebagai hutan. Rincian per jenis di D1 (sumber buku yang sama) memperlihatkan kenaikan ini datang dari
                hutan sekunder dan hutan tanaman, sementara hutan primer justru turun.
            </x-slot:kenapa>
            <x-slot:lanjut>Kalau tutupan naik, apa yang masih menggerus hutan? Dua tekanan yang diperiksa: kebakaran (D3) dan produksi kayu (D4).</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartTutupanProvinsi" kode="D2" ikon="daun" judul="Perubahan tutupan hutan per provinsi, 2018 → {{ $tutupan['tahun_akhir'] }}"
          ket="ha · 8 penurunan terbesar (merah karat) dan 5 kenaikan terbesar (hijau)">
    <x-slot:ringkas>
        Meski total nasional naik, {{ $tutupan['jumlah_turun'] }} dari {{ $tutupan['jumlah_provinsi'] }} provinsi tetap kehilangan hutan.
    </x-slot:ringkas>
</x-grafik>
