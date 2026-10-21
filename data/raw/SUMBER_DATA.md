# Sumber Data Mentah

Diunduh: 20 Oktober 2026 (D1: 21 Oktober 2026). File di folder ini adalah file asli dari sumber, cuma diganti nama. **Jangan diedit.**

| Kode | File | Sumber | Link | Tahun tersedia | Diperbarui (sumber) |
|---|---|---|---|---|---|
| D1 | `xls/D1_tutupan_kelas_2014_2023.xls` | BPS, tabel statis 2084 (asal: KLHK, *Buku Rekalkulasi Penutupan Lahan Indonesia Tahun 2014–2023*) | https://www.bps.go.id/id/statistics-table/1/MjA4NCMx/luas-penutupan-lahan-indonesia-di-dalam-dan-di-luar-kawasan-hutan-tahun-2014-2018-menurut-kelas-ribu-ha-.html (slug URL masih "2014-2018", isi tabel sudah 2014–2023) | 2014–2023, nasional | 17 Feb 2025 |
| D2 | `csv/D2_tutupan_lahan_2014_2023.csv` | BPS (asal: KLHK, *Buku Rekalkulasi Penutupan Lahan Indonesia 2023*) | https://www.bps.go.id/id/statistics-table/1/MjExMCMx/rekapitulasi-luas-penutupan-lahan-hutan-dan-non-hutan-menurut-provinsi-tahun-2014-2022--ribu-ha-.html | 2014–2023 | 17 Feb 2025 |
| D3 | `xlsx/D3_karhutla_provinsi_2018_2026.xlsx` | SiPongi KLHK/Kemenhut (tab "List Data Provinsi") | https://sipongi.gakkum.kehutanan.go.id/indikasi-luas-kebakaran | 2018–2026 (2026 baru Jan–Jul) | data input s.d. Juli 2026 |
| D4 | `json/D4_produksi_kayu_bulat_{2018..2025}.json` (satu file per tahun, tombol JSON di halaman tabel) | BPS, tabel dinamis var 2165 (asal: Data Kehutanan Triwulanan) | https://www.bps.go.id/id/statistics-table/2/MjE2NSMy/produksi-kayu-bulat-menurut-provinsi.html | 2012–2025 | 28 Agu 2026 |
| D5 | `csv/D5_deforestasi_netto_2013_2022.csv` | BPS | https://www.bps.go.id/id/statistics-table/1/MjA4MSMx/angka-deforestasi-netto-indonesia-di-dalam-dan-di-luar-kawasan-hutan-tahun-2013-2022-ha-th-.html | periode 2013–2014 s.d. 2021–2022, per provinsi | 15 Jan 2024 |

Kewajiban sitasi SiPongi: "Sumber: SIPONGI KEMENHUT".

## Catatan untuk tahap transformasi
- **D1:** BPS hanya menyediakan file Excel lama `.xls` (tombol "XLSX" di situs menghasilkan file `.xls`), jadi dibaca dengan `xlrd`. Hanya tingkat nasional, tanpa provinsi. Baris = 21 kelas penutupan lahan dalam dua kelompok ("A. Hutan", "B. Non Hutan"); kolom = tiap tahun × (Kawasan Hutan, APL, TOTAL), satuan ribu ha. Baris "Jumlah Hutan", "Jumlah Non Hutan", dan "Total" dipakai untuk cek total. Keterangan BPS di bawah tabel: hutan tanaman = hutan hasil budidaya manusia (HTI maupun hasil reboisasi/penghijauan); tubuh air tidak dihitung.
- **D1 (temuan saat ETL):** total hutan D1 sama dengan baris total nasional D2 di setiap tahun, termasuk 2020 (keduanya dari buku rekalkulasi KLHK). Penurunan hutan primer 2018→2023 hampir seluruhnya terjadi 2022→2023, bersamaan dengan naiknya hutan sekunder; dugaan kami perpindahan kelas, belum diperiksa di buku sumbernya.
- **D2:** header bertingkat (tahun → Hutan/Non Hutan/Jumlah → luas/%). Kolom 2014–2022 memakai 34 provinsi. **2023 ada di blok terpisah di bawah dengan 38 provinsi**, jadi Papua perlu digabung balik ke 34. Pemisah ribuan pakai spasi ("3 156.7").
- **D2 (temuan saat ETL):** Keterangan BPS di bawah tabel: "Data provinsi Kalimantan Timur merupakan data gabungan antara Provinsi Kalimantan Timur dan Kalimantan Utara" — di tabelnya Kaltara kosong 2014–2020 dan terisi mulai 2021; ETL menggabung Kaltara ke Kaltim untuk semua tahun agar konsisten.
- **D3:** sudah memuat kode wilayah (kode provinsi BPS). 2026 belum setahun penuh, jadi jangan dipakai di tren.
- **D4:** satu file JSON per tahun. Kunci `datacontent` = kode provinsi + var (2165) + turvar (0) + kode tahun + turtahun (0); contoh `1100216501250` = Aceh 2025. 2018–2022 berisi 34 provinsi, 2023–2025 berisi 38 provinsi; semuanya ditambah baris INDONESIA (kode 9999) yang harus dibuang saat transformasi supaya tidak terhitung dobel. Satuan m³.
- **D5:** nilai per periode dua tahun, bisa negatif (reforestasi lebih besar dari deforestasi).
