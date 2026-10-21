# =====================================================================
# Setup lokal (Windows / PowerShell): schema Postgres/Supabase, pipeline ETL, dan website.
#   Prasyarat: project Supabase (atau Postgres lain), Python 3.10+, PHP 8.3+ (ekstensi pdo_pgsql), Composer, Node.js 20+.
#   1. Salin .env.example menjadi .env dan isi DATABASE_URL (lihat petunjuk di dalamnya).
#   2. Jalankan:  powershell -ExecutionPolicy Bypass -File setup.ps1
#   Setelah selesai:  cd web; php artisan serve   -> http://localhost:8000
# =====================================================================
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

function Langkah($teks) { Write-Host "`n==> $teks" -ForegroundColor Green }
function Gagal($teks) { Write-Host "`nGagal: $teks" -ForegroundColor Red; exit 1 }
function Jalankan($perintah) { & cmd /c $perintah; if ($LASTEXITCODE -ne 0) { Gagal "perintah gagal: $perintah" } }

foreach ($p in 'php', 'composer', 'npm') {
    if (-not (Get-Command $p -ErrorAction SilentlyContinue)) { Gagal "'$p' belum terpasang (lihat Prasyarat di README)." }
}
$python = if (Get-Command py -ErrorAction SilentlyContinue) { 'py -3' } elseif (Get-Command python -ErrorAction SilentlyContinue) { 'python' } else { Gagal "Python belum terpasang." }

if (-not (Test-Path .env)) { Copy-Item .env.example .env; Gagal 'File .env baru dibuat. Isi DATABASE_URL di .env, lalu jalankan ulang setup.ps1' }
if (-not (Select-String -Path .env -Pattern '^DATABASE_URL=postgres' -Quiet) -or (Select-String -Path .env -Pattern '<password>' -Quiet)) { Gagal 'Isi DATABASE_URL di .env dengan connection string Supabase.' }
if (-not (Test-Path web\.env)) { Copy-Item web\.env.example web\.env; Gagal 'web\.env dibuat. Isi DB_HOST, DB_USERNAME, DB_PASSWORD di web\.env (sama dengan Supabase), lalu jalankan ulang.' }

Langkah 'Memasang library Python (virtual env .venv)'
Jalankan "$python -m venv .venv"
Jalankan ".venv\Scripts\python.exe -m pip install -q --disable-pip-version-check -r etl\requirements.txt"

$env:PYTHONIOENCODING = "utf-8"
Push-Location etl
Langkah 'Membuat 3 schema (staging, master, dashboard)'
Jalankan "..\.venv\Scripts\python.exe init_db.py"
Langkah 'Menjalankan pipeline ETL (extract -> transform/load -> replikasi)'
Jalankan "..\.venv\Scripts\python.exe run_etl.py"
Pop-Location

Langkah 'Menyiapkan website Laravel'
Push-Location web
Jalankan 'composer install --no-interaction --quiet'
Jalankan 'npm install --silent'
Jalankan 'npm run build --silent'
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+' -Quiet)) { Jalankan 'php artisan key:generate --force' }
Jalankan 'php artisan migrate --seed --force'
Pop-Location

Write-Host "`nSelesai. Jalankan:`n`n    cd web; php artisan serve`n`nlalu buka http://localhost:8000 (akun demo ada di halaman login)." -ForegroundColor Green
