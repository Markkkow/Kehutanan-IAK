"""Jalankan seluruh pipeline: EXTRACT -> TRANSFORM + LOAD -> REPLICATION.

  python run_etl.py            # baca semua file mentah di data/raw
"""
import extract
import replicate
import transform_load
from config import run_id


def main():
    rid = run_id()
    print(f"=== ETL run {rid} ===")
    extract.main(rid)
    transform_load.main(rid)
    replicate.main()
    print("=== selesai ===")


if __name__ == "__main__":
    main()
