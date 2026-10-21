@use('App\Support\Format', 'F')

<x-grafik id="chartKarhutlaTren" jangkar="karhutla" kode="D3" ikon="api" judul="Luas kebakaran hutan & lahan per tahun"
          ket="SiPongi KLHK · XLSX · ha · batang pucat 2026 baru Januari–Juli">
    <x-slot:ringkas>
        Dua lonjakan: {{ $karhutla['puncak'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak']]) }} ha) dan
        {{ $karhutla['puncak_kedua'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]) }} ha); tahun lain jauh lebih kecil.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Ada dua lonjakan: {{ $karhutla['puncak'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak']]) }} ha) dan
                {{ $karhutla['puncak_kedua'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]) }} ha); tahun lain jauh lebih kecil.
                Data 2026 baru tercatat sampai Juli, jadi digambar pucat dan tidak dibandingkan dengan tahun penuh.
            </x-slot:terlihat>
            <x-slot:kenapa>
                2019 dan 2023 adalah tahun El Niño: kemarau lebih panjang dan kering, sehingga lahan gambut di Kalimantan dan Sumatera mudah terbakar dan
                sulit dipadamkan. Pembukaan lahan dengan cara membakar memperparah kondisi ini.
            </x-slot:kenapa>
            <x-slot:lanjut>Kebakaran bersifat musiman. Tekanan yang lebih rutin datang dari produksi kayu (D4).</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartKarhutlaProvinsi" kode="D3" ikon="api" judul="10 provinsi dengan karhutla terluas" ket="ha">
    <x-slot:aksi>
        <select id="pilihTahunKarhutla" class="pilih py-1 pl-2.5 text-xs" aria-label="Tahun karhutla">
            @foreach ($karhutla['tahun'] as $t)
                <option value="{{ $t }}" @selected($t == $karhutla['puncak'])>{{ $karhutla['label_tahun'][$t] ?? $t }}</option>
            @endforeach
        </select>
    </x-slot:aksi>
    <x-slot:ringkas>
        Pada {{ $karhutla['puncak'] }}, {{ $karhutla['wilayah_puncak']->join(' dan ') }} menyumbang
        {{ F::persen($karhutla['wilayah_puncak_persen'], 0) }} luas terbakar.
    </x-slot:ringkas>
</x-grafik>
