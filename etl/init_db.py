"""Buat (atau reset) tiga schema kehutanan di Postgres/Supabase dari sql/schema.sql.

  python init_db.py
"""
from config import ROOT, koneksi


def main():
    sql = (ROOT / "sql" / "schema.sql").read_text(encoding="utf-8")
    conn = koneksi()
    try:
        conn.execute(sql)  # beberapa perintah sekaligus (tanpa parameter)
        conn.commit()
    finally:
        conn.close()
    print("[init_db] schema kehutanan_staging, kehutanan_master, kehutanan_dashboard siap.")


if __name__ == "__main__":
    main()
