import os
from datetime import datetime
from pathlib import Path

import psycopg
from dotenv import load_dotenv

ROOT = Path(__file__).resolve().parent.parent
RAW_DIR = ROOT / "data" / "raw"
load_dotenv(ROOT / ".env")

# Tiga schema di satu database Postgres (Supabase). Nama dipakai sebagai awalan tabel: kehutanan_master.fakta_karhutla
DB_STAGING = "kehutanan_staging"
DB_MASTER = "kehutanan_master"
DB_DASHBOARD = "kehutanan_dashboard"

TAHUN_MIN, TAHUN_MAKS = 2018, 2025


def koneksi(database=None):
    """Sambungkan ke Postgres/Supabase. Semua tabel ditulis dengan awalan schema, jadi argumen `database` hanya
    dipertahankan agar pemanggil lama tetap jalan.

    Isi DATABASE_URL di .env (connection string Supabase). Untuk ETL pakai koneksi langsung atau Session pooler
    (port 5432), BUKAN Transaction pooler (port 6543).
    """
    url = os.getenv("DATABASE_URL")
    if url:
        return psycopg.connect(url, autocommit=False)
    return psycopg.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "5432")),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
        dbname=os.getenv("DB_NAME", "postgres"),
        sslmode=os.getenv("DB_SSLMODE", "prefer"),
        autocommit=False,
    )


def sekarang():
    return datetime.now().replace(microsecond=0)


def run_id():
    return datetime.now().strftime("%Y%m%d-%H%M%S")


def catat_etl(conn, rid, tahap, dataset, masuk, dimuat, dibuang, status, pesan=""):
    with conn.cursor() as cur:
        cur.execute(
            f"INSERT INTO {DB_MASTER}.etl_log (run_id, tahap, dataset, baris_masuk, baris_dimuat, "
            "baris_dibuang, status, pesan, waktu) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
            (rid, tahap, dataset, masuk, dimuat, dibuang, status, pesan, sekarang()),
        )
