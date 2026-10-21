# Dashboard Kehutanan Indonesia

Tugas Integrasi Aplikasi Korporasi (Enterprise Application Integration) — Kelompok 3, S1 Teknologi Sains Data FTMM UNAIR.

> **Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?**
> Lima data kehutanan (BPS, KLHK/SiPongi) dalam empat format file berbeda diintegrasikan lewat pipeline ETL
> ke Postgres (Supabase), lalu ditampilkan di dashboard Laravel dengan login. Website dideploy ke Vercel.

## Data

| Kode | Data | Sumber | Format | Cara extract |
|---|---|---|---|---|
| D1 | Tutupan lahan nasional menurut kelas, di dalam & di luar kawasan hutan (2018–2023) | BPS / KLHK | XLS | unduh file |
| D2 | Tutupan lahan hutan & non-hutan per provinsi (2018–2023) | BPS / KLHK | CSV | unduh file |
| D3 | Luas kebakaran hutan & lahan per provinsi (2018–2025, + 2026 Januari–Juli) | SiPongi KLHK | XLSX | unduh file |
| D4 | Produksi kayu bulat per provinsi (2018–2025) | BPS | JSON | unduh file (per tahun) |
| D5 | Deforestasi netto per provinsi (2017–2018 s.d. 2021–2022) | BPS / KLHK | CSV | unduh file |

Link sumber dan catatan tiap file: [`data/raw/SUMBER_DATA.md`](data/raw/SUMBER_DATA.md).

## Arsitektur (three-tier)

```
 data/raw (XLS, XLSX,       EXTRACT        TRANSFORM + LOAD         REPLICATION            PRESENTATION
       CSV, JSON) ─────────────────► kehutanan_staging ──────► kehutanan_master ──────► replika ──► Laravel (web/)
                                     (teks mentah)              (bersih, kode BPS)       (schema kehutanan_dashboard)
                                                                      ▲
         file sumber terbaru (D1–D5) ── SYNCHRONIZATION (one-way, hanya selisih yang diterapkan)
```

| Teknik Data-level EAI | File | Bukti di dashboard |
|---|---|---|
| Extract | `etl/extract.py` | Log ETL tahap `extract` |
| Transformation | `etl/transform_load.py` (aturan T1–T8), `etl/angka.py`, `etl/provinsi.py` | Log ETL tahap `transform-load` + cek total |
| Replication | `etl/replicate.py` (inkremental berbasis primary key + checksum MD5), schema master → schema replika | Kartu "Replikasi master → replika" (jumlah baris, checksum, status) |
| Synchronization | `etl/sync.py` (file sumber terbaru D1–D5 → master, one-way, hanya selisih) | Kartu "Synchronization" + tabel sinkronisasi per data |

## Database: Supabase (Postgres)

Satu database Postgres dengan tiga schema (`kehutanan_staging`, `kehutanan_master`, `kehutanan_dashboard`) dibuat dari
`sql/schema.sql`. Tabel milik Laravel (users, migrations) ada di schema `public` dan diamankan dengan Row Level Security
(migrasi `2026_10_22_000000_amankan_tabel_laravel_supabase.php`) agar tidak terbaca lewat Data API / anon key Supabase.

Tiga jenis connection string Supabase (Project Settings → Database → Connection string):

| Dipakai untuk | Jenis | Port |
|---|---|---|
| ETL & migrasi dari laptop | Koneksi langsung (perlu IPv6) atau **Session pooler** (IPv4) | 5432 |
| Website di Vercel (serverless) | **Transaction pooler**, user `postgres.<project-ref>` | 6543 |

## Menjalankan lokal

Prasyarat: project **Supabase**, **Python 3.10+**, **PHP 8.3+** (ekstensi `pdo_pgsql`), **Composer**, **Node.js 20+**.

```bash
git clone <url-repo-anda>
cd dashboard-kehutanan
cp .env.example .env            # isi DATABASE_URL (Supabase)
cp web/.env.example web/.env    # isi DB_HOST, DB_USERNAME, DB_PASSWORD (Supabase)
./setup.sh                      # macOS / Linux
# powershell -ExecutionPolicy Bypass -File setup.ps1   (Windows)
cd web && php artisan serve     # buka http://localhost:8000
```

Script setup membuat 3 schema dari `sql/schema.sql`, memasang library Python di `.venv`, menjalankan pipeline ETL dari file
mentah di `data/raw`, lalu menyiapkan Laravel (composer, npm build, key, migrasi, akun demo). Aman dijalankan ulang.

Langkah manual: `python etl/init_db.py` (buat schema) → `python etl/run_etl.py` → `cd web && composer install && npm install && npm run build && php artisan key:generate && php artisan migrate --seed`.

### Akun

Halaman login menampilkan akun demo dengan tombol **"Masuk sebagai …"** yang mengisi email & password otomatis
(daftarnya di `web/config/akun_demo.php`). Admin bisa mengelola akun lewat tombol **Akun** di kanan atas dashboard; peran
*pengguna* hanya melihat dashboard. Password akun disimpan sebagai hash bcrypt.

## Deploy ke Vercel

Vercel tidak mendukung PHP secara resmi. Proyek ini memakai runtime komunitas
[`vercel-php`](https://github.com/vercel-community/php) (`vercel-php@0.9.0`, PHP 8.5, sudah menyertakan `pdo_pgsql`).
Cek versi terbarunya di npm sebelum deploy.

1. **Siapkan database** (sekali, dari laptop): isi `.env` lalu `cd etl && python init_db.py && python run_etl.py`, kemudian
   di `web/.env` arahkan ke Supabase dan jalankan `php artisan migrate --seed --force` supaya tabel users dan akun demo ada.
2. **Push ke GitHub.** Folder `web/public/build` (hasil `npm run build`) ikut dikomit karena Vercel tidak menjalankan Vite.
   Setelah mengubah CSS/JS, jalankan `cd web && npm run build` lagi lalu komit.
3. **Import repo di Vercel**, lalu atur **Root Directory = `web`**, Framework Preset = *Other*, kosongkan Build Command.
4. **Environment Variables** (Project Settings → Environment Variables):

   | Nama | Isi |
   |---|---|
   | `APP_KEY` | hasil `php artisan key:generate --show` |
   | `APP_URL` | URL Vercel, mis. `https://nama-proyek.vercel.app` |
   | `DB_CONNECTION` | `pgsql` |
   | `DB_HOST` | host **Transaction pooler** Supabase (`aws-0-<region>.pooler.supabase.com`) |
   | `DB_PORT` | `6543` |
   | `DB_DATABASE` | `postgres` |
   | `DB_USERNAME` | `postgres.<project-ref>` |
   | `DB_PASSWORD` | password database Supabase |
   | `DB_SSLMODE` | `require` |

   `vercel.json` sudah mengatur `SESSION_DRIVER=cookie`, `CACHE_STORE=array`, `LOG_CHANNEL=stderr`, dan memindahkan
   storage/cache Laravel ke `/tmp` (sistem file Vercel hanya-baca).
5. **Deploy.** Data diperbarui dengan menjalankan ETL/sinkronisasi dari laptop (atau GitHub Actions), bukan dari Vercel.

Catatan: runtime `vercel-php` adalah proyek komunitas dan tiap permintaan menjalankan Laravel dari nol (cold start),
jadi halaman pertama bisa terasa lambat.

### Replikasi di Supabase

Replikasi data dilakukan `etl/replicate.py` antar schema (`kehutanan_master` → `kehutanan_dashboard`) dengan pembanding
checksum, dan website hanya membaca schema replika. Bila ingin replikasi tingkat server, Supabase menyediakan **Read Replica**
(fitur berbayar, cek plan Anda): isi `DATA_DB_HOST` dengan host replica agar website membaca dari sana.

### Demo untuk presentasi

Naskah lengkap (langkah, kalimat yang diucapkan, antisipasi pertanyaan): **[`docs/NASKAH_DEMO.md`](docs/NASKAH_DEMO.md)**.

| Demo | Perintah (dari folder repo) |
|---|---|
| Transformation | `python scripts/demo_transformasi.py` |
| Replication + Synchronization | `cd etl && python sync.py --simulasi D4` |

### Sinkronisasi (dan demonya untuk presentasi)

```bash
cd etl
../.venv/bin/python sync.py                  # sinkronkan D1–D5 dengan file sumber terbaru
../.venv/bin/python sync.py --simulasi D2    # demo; ganti D2 dengan D1–D5
```

Beda dengan ETL penuh (`run_etl.py`), sinkronisasi hanya menerapkan **selisih**: file sumber terbaru dibaca dengan aturan
transformasi yang sama, dibandingkan dengan master per primary key, lalu baris baru disisipkan, baris berubah diperbarui,
dan baris yang tidak ada lagi di sumber dihapus. Hasilnya dicatat per data di `sync_log`. Mode `--simulasi` mengubah satu
nilai di master menjadi 0, menunggu Enter, lalu sinkronisasi mengembalikannya. Refresh dashboard sebelum dan sesudah Enter.

## Struktur folder

```
dashboard-kehutanan/
├── data/raw/{xls,xlsx,csv,json}/  file asli dari sumber (jangan diedit)
├── etl/                        pipeline Python
├── scripts/demo_transformasi.py demo transformasi untuk presentasi
├── docs/NASKAH_DEMO.md         naskah demo presentasi
├── setup.sh, setup.ps1         setup satu perintah (macOS/Linux, Windows)
├── sql/schema.sql              skema 3 schema Postgres
└── web/                        Laravel (deploy ke Vercel: vercel.json, api/index.php)
    ├── routes/web.php
    ├── app/Http/Controllers/   AuthController, DashboardController, AkunController (CRUD akun)
    ├── app/Models/             satu model per tabel
    ├── app/Services/           DashboardService (olah data untuk grafik)
    ├── resources/views/        auth/, dashboard/, dashboard/partials/, components/ (x-grafik, x-insight, x-judul-panel, x-ikon)
    ├── resources/css/app.css   Tailwind: token warna & komponen (kartu, insight, tabel)
    └── resources/js/dashboard.js  semua grafik Chart.js + tabel pendamping
```

Aturan visualisasi yang dipakai: satu sumbu per grafik (satuan berbeda dibandingkan lewat indeks 2018 = 100), warna = identitas
data dan konsisten di seluruh halaman (palet lolos uji buta warna), garis lurus untuk data tahunan, label langsung hanya di titik
penting, dan setiap grafik punya **tabel data** pendamping.

## Keterbatasan data (ringkas)

- D1 hanya tersedia di tingkat nasional (tanpa rincian provinsi), jadi tidak ada di peta sebaran.
- D2 dan D5 belum memisahkan Kalimantan Utara dari Kalimantan Timur; untuk analisis gabungan keduanya disatukan.
- D5 baru tersedia s.d. periode 2021–2022. D3 2026 baru berisi Januari–Juli: ditampilkan pucat di grafik D3 dan peta,
  tetapi tidak dibandingkan dengan tahun penuh.
- D2 (rekalkulasi 2023) dan D5 (buku deforestasi per periode) tidak saling cocok; ditampilkan di bagian Validasi silang.
