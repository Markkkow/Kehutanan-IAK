#!/usr/bin/env bash
# =====================================================================
# Setup lokal (macOS / Linux): schema Postgres/Supabase, pipeline ETL, dan website.
#   Prasyarat: project Supabase (atau Postgres lain), Python 3.10+, PHP 8.3+ (ekstensi pdo_pgsql), Composer, Node.js 20+.
#   1. Salin .env.example menjadi .env dan isi DATABASE_URL (lihat petunjuk di dalamnya).
#   2. Jalankan:  ./setup.sh
#   Setelah selesai:  cd web && php artisan serve   -> http://localhost:8000
# Menjalankan ulang aman: 3 schema kehutanan dibuat ulang dari file mentah di data/raw.
# =====================================================================
set -euo pipefail
cd "$(dirname "$0")"

langkah() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
gagal()   { printf '\n\033[1;31mGagal:\033[0m %s\n' "$*" >&2; exit 1; }

for p in python3 php composer npm; do
    command -v "$p" >/dev/null || gagal "'$p' belum terpasang (lihat Prasyarat di README)."
done

[ -f .env ] || { cp .env.example .env; gagal "File .env baru dibuat. Isi DATABASE_URL di .env, lalu jalankan ulang ./setup.sh"; }
grep -qE '^DATABASE_URL=postgres' .env && ! grep -q '<password>' .env || gagal "Isi DATABASE_URL di .env dengan connection string Supabase."
[ -f web/.env ] || { cp web/.env.example web/.env; echo "web/.env dibuat. Isi DB_HOST, DB_USERNAME, DB_PASSWORD di web/.env (sama dengan Supabase), lalu jalankan ulang."; exit 1; }

langkah "Memasang library Python (virtual env .venv)"
python3 -m venv .venv
.venv/bin/pip install -q --disable-pip-version-check -r etl/requirements.txt

langkah "Membuat 3 schema (staging, master, dashboard)"
(cd etl && ../.venv/bin/python init_db.py)

langkah "Menjalankan pipeline ETL (extract -> transform/load -> replikasi)"
(cd etl && ../.venv/bin/python run_etl.py)

langkah "Menyiapkan website Laravel"
cd web
composer install --no-interaction --quiet
npm install --silent
npm run build --silent
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force
php artisan migrate --seed --force

printf '\n\033[1;32mSelesai.\033[0m Jalankan:\n\n    cd web && php artisan serve\n\nlalu buka http://localhost:8000 (akun demo ada di halaman login).\n'
