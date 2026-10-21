@use('App\Support\Format', 'F')

<x-grafik id="chartKayuTren" jangkar="kayu" kode="D4" ikon="kayu" judul="Produksi kayu bulat nasional per tahun" ket="BPS · JSON · m³">
    <x-slot:ringkas>
        Naik terus hingga puncaknya pada {{ $kayu['puncak'] }} ({{ F::ringkas($kayu['nasional'][$kayu['puncak']]) }} m³), lalu turun.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Produksi naik terus hingga puncaknya pada {{ $kayu['puncak'] }} ({{ F::ringkas($kayu['nasional'][$kayu['puncak']]) }} m³), lalu turun.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Sebagian besar kayu bulat berasal dari hutan tanaman industri (HTI) untuk bahan baku pulp dan kertas, terutama di Riau, Sumatera Selatan,
                dan Kalimantan. Produksi tinggi tidak otomatis berarti hutan alam rusak, karena yang ditebang banyak berupa tanaman yang memang ditanam
                untuk dipanen.
            </x-slot:kenapa>
            <x-slot:lanjut>Seberapa banyak hutan yang benar-benar hilang? Data D5 melihat laju deforestasi netto.</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartKayuProvinsi" kode="D4" ikon="kayu" judul="10 provinsi produsen kayu terbesar" ket="m³">
    <x-slot:aksi>
        <select id="pilihTahunKayu" class="pilih py-1 pl-2.5 text-xs" aria-label="Tahun produksi kayu">
            @foreach ($kayu['tahun'] as $t)
                <option value="{{ $t }}" @selected($loop->last)>{{ $t }}</option>
            @endforeach
        </select>
    </x-slot:aksi>
    <x-slot:ringkas>
        Pada {{ $kayu['tahun']->last() }}, {{ $kayu['teratas'] }} sendiri menghasilkan {{ F::persen($kayu['teratas_persen'], 0) }} kayu bulat nasional;
        tiga provinsi teratas {{ F::persen($kayu['top3_persen'], 0) }}.
    </x-slot:ringkas>
</x-grafik>
