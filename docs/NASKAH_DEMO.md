# Naskah Demo: Dashboard Kehutanan (Kelompok 3)

Panduan memperagakan tiga teknik **Data-level EAI** (Transformation, Replication, Synchronization) saat presentasi.
Durasi demo ±8 menit. Setiap langkah berisi **yang dilakukan**, **yang terlihat**, dan **yang diucapkan**.

---

## 0. Persiapan (10 menit sebelum presentasi)

Jalankan dari folder repo `dashboard-kehutanan`:

| # | Cek | Perintah / cara |
|---|---|---|
| 1 | Database Supabase aktif dan terisi | buka Supabase → Table Editor, schema `kehutanan_master` berisi data; bila kosong: `cd etl && python init_db.py && python run_etl.py` |
| 2 | Website menyala | buka URL Vercel, atau lokal: `cd web && php artisan serve` (biarkan Terminal ini terbuka) |
| 3 | Dashboard terbuka & sudah login | klik **"Masuk sebagai Admin Kelompok 3"** → **Masuk** |
| 4 | Siapkan Terminal kedua di folder repo | untuk perintah demo di bawah |

Di dashboard, buka bagian **Integrasi data**: kartu **Replication** harus bertanda ✓ (semua tabel identik).

> Python: perintah di bawah memakai `python3`. Kalau proyek disiapkan dengan `./setup.sh`, ganti dengan `../.venv/bin/python`
> (Windows: `..\.venv\Scripts\python.exe`).

Demo Transformation: `python scripts/demo_transformasi.py`. Demo Replication + Synchronization: `cd etl && python sync.py --simulasi D4`.
Replikasi berjalan lewat `etl/replicate.py` antar schema di Supabase, dibuktikan dengan checksum yang sama.

---

## 1. Pembuka (30 detik)

**Tampilkan:** bagian **Integrasi data** (klik menu *Integrasi data* di atas), arahkan ke flowchart.

**Ucapkan:**
> "Dashboard ini menggabungkan lima data kehutanan dari BPS dan KLHK dalam empat format file: XLS, XLSX, CSV, dan JSON.
> Alurnya seperti di flowchart: file sumber dibaca ke database **staging**, dibersihkan masuk ke **master**, lalu master
> **direplikasi** ke server kedua yang dibaca website. Kalau sumber memperbarui angka, master **disinkronkan**.
> Tiga kotak hijau ini adalah tiga teknik Data-level EAI yang kami peragakan sekarang. Semuanya langsung antar-database,
> tanpa API."

---

## 2. Demo TRANSFORMATION (±3 menit)

**Lakukan** (Terminal kedua):

```bash
python scripts/demo_transformasi.py
```

Script menampilkan data mentah (staging) dan data bersih (master) berdampingan, lalu berhenti tiap bagian. Tekan **Enter**
untuk lanjut.

| Bagian di layar | Yang terlihat | Yang diucapkan |
|---|---|---|
| **T1 + T4** angka & satuan | Riau 2019 ditulis `2 459.2` (D2), `90.550,00` (D3), `142,011.1` (D5). Di master jadi `2459200.00`, `90550.00`, `142011.10` | "Tiap sumber menulis angka berbeda: ada yang pakai spasi, titik, atau koma sebagai pemisah ribuan. D2 juga masih dalam **ribu** hektar. Semua kami seragamkan jadi angka biasa dalam hektar." |
| **T2** nama provinsi | `KEP. BANGKA BELITUNG`, `Bangka Belitung`, `Kepulauan Bangka Belitung` → kode `19` | "Satu provinsi ditulis tiga cara. Semuanya kami ganti dengan kode resmi BPS supaya bisa digabung." |
| **T3** pemekaran Papua | sumber 2025 berisi 38 provinsi (ada Papua Tengah, Papua Pegunungan, dll.), master 34 | "Sejak 2022 Papua dipecah. Supaya bisa dibandingkan antartahun, provinsi baru digabung balik ke induknya." |
| **T7** cek total | semua data `OK (selisih 0.00%)` | "Setelah dibersihkan, jumlah semua provinsi kami cocokkan dengan baris total nasional di file sumber. Selisihnya 0%, artinya tidak ada data yang hilang atau terhitung dua kali." |

Daftar lengkap aturan T1–T8 ada di **Lampiran A**.

---

## 3. Demo REPLICATION + SYNCHRONIZATION (±4 menit, satu perintah)

**Lakukan** (Terminal kedua):

```bash
cd etl
python3 sync.py --simulasi D4
```

### Langkah 3a: data di Master diubah

**Yang terlihat di Terminal:**
```
[simulasi] D4: produksi kayu Riau 2025 di master diubah jadi 0 dan sudah sampai ke replika.
[simulasi] Cek dashboard (angkanya berubah), lalu tekan Enter untuk menjalankan sinkronisasi...
```
**Jangan tekan Enter dulu.**

**Ucapkan:**
> "Sekarang kami sengaja mengubah satu angka **hanya di database master**: produksi kayu Riau 2025 dijadikan 0."

### Langkah 3b: buktikan REPLICATION

**Lakukan:** refresh dashboard → menu **Per data** → kartu **D4 · 10 provinsi dengan produksi kayu terbesar** (tahun 2025).

**Yang terlihat:** Riau, yang biasanya peringkat 1 (±23,6 jt m³), **hilang** dari grafik.

**Ucapkan:**
> "Kami tidak menyentuh schema yang dibaca website, tapi perubahannya sudah sampai. Inilah **replication**: proses replikasi
> membandingkan schema master dengan schema replika berdasarkan primary key, menyalin baris yang berbeda, lalu mencocokkan
> checksum kedua sisi. Website hanya membaca schema replika."

(Opsional) tunjukkan buktinya di kartu **Replikasi master → replika** di bagian Integrasi: kolom `+ / ~ / −` menunjukkan
baris yang disisipkan, diperbarui, dan dihapus, dan statusnya **SINKRON**.

### Langkah 3c: jalankan SYNCHRONIZATION

**Lakukan:** kembali ke Terminal, tekan **Enter**.

**Yang terlihat:**
```
[sync] D4: dicek 272 | baru 0 | berubah 1 | dihapus 0 | sama 271
       berubah  Riau 2025 (produksi_m3: 0.0 -> 23561716.35)
```

**Ucapkan:**
> "Sinkronisasi membandingkan master dengan **file sumber terbaru** baris per baris. Dari 272 baris, hanya **satu** yang
> berbeda, dan hanya baris itu yang diperbaiki. 271 baris lain tidak disentuh. Arahnya satu: dari sumber ke master. Kalau
> nanti BPS merevisi angka, kami cukup mengunduh ulang filenya lalu menjalankan sinkronisasi ini."

### Langkah 3d: tunjukkan hasilnya

**Lakukan:** refresh dashboard → Riau **kembali** di peringkat 1. Lalu buka **Integrasi data → Lihat detail teknis** →
tabel **Sinkronisasi file sumber → master**: baris D4 menunjukkan *berubah 1*.

**Ucapkan (penutup demo):**
> "Jadi tiga teknik ini saling melengkapi: **transformation** membersihkan data sumber ke master, **replication** menyalin
> master ke server yang dibaca website, dan **synchronization** menjaga master tetap sama dengan sumbernya."

---

## 4. Penutup (30 detik)

> "Hasil akhirnya dashboard yang membaca data bersih dan konsisten dari lima sumber. Semua pemeriksaan (cek total,
> checksum replikasi, log sinkronisasi) bisa dilihat di bagian Integrasi data."

---

## Lampiran A: Aturan transformasi T1–T8

Kode lengkapnya di `etl/transform_load.py`.

| Aturan | Masalah di data mentah (contoh asli) | Yang dilakukan |
|---|---|---|
| **T1** Format angka | Riau ditulis `90.550,00` (D3), `142,011.1` (D5), `2 459.2` (D2) | Diubah jadi angka biasa. Tanpa ini `90.550` bisa terbaca 90,55. |
| **T2** Nama provinsi | `KEP. BANGKA BELITUNG`, `Bangka Belitung`, `Kepulauan Bangka Belitung` | Diganti kode BPS 2 digit. Baris total `INDONESIA` dipisah agar tidak terhitung dobel. |
| **T3** Pemekaran Papua | Sejak 2023 ada 6 provinsi Papua (38 provinsi) | Digabung ke induknya agar tiap tahun 34 provinsi. |
| **T4** Satuan | D1 & D2 dalam **ribu** ha | Dikali 1.000 → hektar. |
| **T5** Bentuk tabel | Tabel melebar: tahun ke samping, judul kolom bertingkat | Diubah jadi "panjang": 1 baris = 1 provinsi + 1 tahun. |
| **T6** Periode | Sumber berisi data sejak 2013/2014 | Disaring 2018–2025; D3 2026 (Januari–Juli) disimpan dan ditandai parsial. |
| **T7** Cek total | Ada risiko baris hilang/dobel | Jumlah semua provinsi dibandingkan dengan baris total di file sumber (selisih harus 0%). |
| **T8** Kelas lahan (D1) | Nama kelas `Hutan tanaman *` (bintang catatan kaki) | Dibersihkan, dikelompokkan primer/sekunder/tanaman, lalu total hutannya dicocokkan dengan D2. |

**Satu kalimat:** *Transformation menyeragamkan angka, nama provinsi, satuan, dan bentuk tabel dari lima sumber, lalu
memeriksa hasilnya: jumlahnya harus sama dengan total di sumber.*

---

## Lampiran B: Pertanyaan yang mungkin ditanyakan

**Apa beda replication dan synchronization?**
Replication menyalin database kita sendiri (master → replika) sehingga isinya **identik**. Synchronization
menyamakan database kita dengan **sumber luar** (file BPS/KLHK), dan hanya menerapkan bagian yang berubah.

**Kenapa perlu database staging?**
Sebagai tempat transit data mentah apa adanya (teks). File asli tidak diubah, setiap langkah transformasi bisa diperiksa,
dan proses bisa diulang kapan saja.

**Kenapa website membaca replika, bukan master?**
Agar jalur baca (website) dan tulis (ETL, sinkronisasi) terpisah: website hanya membaca schema replika, sehingga
data master tidak tersentuh. Bila ingin replika tingkat server, Supabase menyediakan Read Replica.

**Ini Data-level atau Application Interface-level?**
Data-level. Semua data berupa file, dan semua perpindahan data terjadi langsung antar-database. Tidak ada aplikasi yang
saling memanggil lewat API.

**Apa buktinya replikasinya benar?**
Di dashboard, jumlah baris dan checksum MD5 tiap tabel master dan replika dibandingkan, dan semuanya berstatus SINKRON.

**Bagaimana kalau BPS memperbarui data?**
Unduh ulang file sumbernya ke `data/raw`, lalu jalankan `python3 etl/sync.py`. Hanya baris yang berubah yang diperbarui,
lalu `etl/replicate.py` meneruskannya ke replika.

---

## Lampiran C: Kalau ada masalah saat demo

| Gejala | Penyebab | Solusi |
|---|---|---|
| Dashboard tidak bisa dibuka | Website mati atau database Supabase *paused* | `cd web && php artisan serve`; di Supabase klik **Restore** bila project di-pause (paket gratis) |
| Riau tidak kembali setelah demo | Enter belum ditekan / demo terputus | jalankan `python3 sync.py D4` dari folder `etl` |
| Angka D4 belum berubah saat langkah 3b | Halaman belum di-refresh | refresh browser (Cmd+R) |
