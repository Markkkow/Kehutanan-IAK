@use('App\Support\Format', 'F')

<x-grafik id="chartDeforTren" jangkar="deforestasi" kode="D5" ikon="kapak" judul="Deforestasi netto nasional per periode"
          ket="BPS/KLHK · CSV · ha/tahun · di dalam vs di luar kawasan (APL)">
    <x-slot:ringkas>
        Memuncak pada {{ $deforestasi['puncak'] }}, lalu turun tajam. {{ F::persen($deforestasi['dalam_persen'], 0) }} terjadi <em>di dalam</em> kawasan hutan.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Deforestasi netto (hutan yang hilang dikurangi hutan yang tumbuh kembali) memuncak pada {{ $deforestasi['puncak'] }}, lalu turun tajam
                dan bertahan di kisaran 100–120 rb ha per tahun. {{ F::persen($deforestasi['dalam_persen'], 0) }} deforestasi 2018–2022 terjadi
                <em>di dalam</em> kawasan hutan.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Penurunan sejak 2019–2020 bertepatan dengan moratorium permanen izin baru di hutan primer dan gambut (Inpres 5/2019) serta tahun-tahun
                basah 2020–2022. Lonjakan 2018–2019 sejalan dengan kebakaran besar 2019 (D3).
            </x-slot:kenapa>
            <x-slot:lanjut>Apakah provinsi yang banyak terbakar (D3) atau banyak memproduksi kayu (D4) juga paling banyak kehilangan hutan? Analisis gabungan G3 mengujinya.</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartDeforProvinsi" kode="D5" ikon="kapak" judul="10 provinsi dengan deforestasi netto terbesar"
          ket="ha · jumlah periode 2018–2019 s.d. 2021–2022">
    <x-slot:ringkas>
        Lima provinsi teratas menyumbang {{ F::persen($deforestasi['top5_persen'], 0) }} deforestasi nasional, didominasi Kalimantan dan Sumatera.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:kenapa>
                Kalimantan dan Sumatera mendominasi karena di sana ekspansi perkebunan dan hutan tanaman paling luas.
            </x-slot:kenapa>
        </x-insight>
    </x-slot:info>
</x-grafik>
