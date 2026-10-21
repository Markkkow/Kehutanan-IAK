"""EXTRACT: baca file mentah (XLS, XLSX, CSV, JSON) lalu simpan apa adanya (teks) ke kehutanan_staging.

Tidak ada pembersihan di sini. Struktur tabel sumber hanya "diratakan" (satu baris = satu provinsi atau
satu kelas lahan per tahun) supaya bisa disimpan di tabel, tetapi nilai tetap teks asli.
"""
import csv
import json
import re

import openpyxl
import xlrd

from config import DB_STAGING, RAW_DIR, catat_etl, koneksi, run_id, sekarang


def _teks(v):
    return None if v is None else str(v).strip()


def extract_d1():
    """Tabel BPS 2084 (format .xls lama): baris = kelas penutupan lahan, kolom = tahun x (kawasan, APL, total)."""
    path = RAW_DIR / "xls" / "D1_tutupan_kelas_2014_2023.xls"
    ws = xlrd.open_workbook(path).sheet_by_index(0)
    sel = lambda r, c: ws.cell_value(r, c)
    # baris judul tahun: tiap tahun menempati 3 kolom (kawasan hutan, APL, total)
    baris_tahun = next(r for r in range(ws.nrows) if str(sel(r, 0)).strip().lower().startswith("no"))
    tahun_kolom = {c: int(sel(baris_tahun, c)) for c in range(ws.ncols) if isinstance(sel(baris_tahun, c), float)}
    rows, kelompok = [], None
    for r in range(baris_tahun + 2, ws.nrows):
        no, nama = _teks(sel(r, 0)), _teks(sel(r, 1))
        if not nama:
            continue
        if (no or "").lower().startswith(("keterangan", "(", "sumber")):
            break
        if re.match(r"^[AB]\.", nama):  # judul kelompok: "A. Hutan" / "B. Non Hutan"
            kelompok = nama
            continue
        for c, tahun in tahun_kolom.items():
            rows.append((path.name, r + 1, kelompok, no or None, nama, tahun,
                         _teks(sel(r, c)), _teks(sel(r, c + 1)), _teks(sel(r, c + 2))))
    return rows


def extract_d2():
    path = RAW_DIR / "csv" / "D2_tutupan_lahan_2014_2023.csv"
    semua = list(csv.reader(open(path, encoding="utf-8-sig")))
    rows = []
    blok, tahun_kolom = None, {}
    for n, r in enumerate(semua, start=1):
        if r and r[0].strip() == "No":
            # baris judul tahun: 2014 di kolom 2, 2015 di kolom 7, dst (5 kolom per tahun)
            tahun_kolom = {i: int(v) for i, v in enumerate(r) if v.strip().isdigit()}
            blok = "2014-2022" if 2014 in tahun_kolom.values() else "2023"
            continue
        if not tahun_kolom or len(r) < 3:
            continue
        nama = r[1].strip() or r[0].strip()
        if not nama or nama.startswith("(") or not any(c.strip() for c in r[2:]):
            continue
        if nama.lower().startswith(("keterangan", "sumber")):
            continue
        for i, tahun in tahun_kolom.items():
            if not r[i].strip():
                continue
            rows.append((path.name, blok, n, nama, tahun, r[i], r[i + 1], r[i + 2], r[i + 3], r[i + 4]))
    return rows


def extract_d3():
    path = RAW_DIR / "xlsx" / "D3_karhutla_provinsi_2018_2026.xlsx"
    ws = openpyxl.load_workbook(path, data_only=True).active
    semua = list(ws.iter_rows(values_only=True))
    tahun_kolom = {i: int(v) for i, v in enumerate(semua[0]) if str(v).strip().isdigit()}
    rows = []
    for n, r in enumerate(semua[1:], start=2):
        if not r[1]:
            continue
        for i, tahun in tahun_kolom.items():
            rows.append((path.name, n, _teks(r[0]), _teks(r[1]), tahun, _teks(r[i])))
    return rows


def baris_dari_json_bps(data, nama_sumber):
    """Bongkar file JSON tabel dinamis BPS menjadi baris provinsi-tahun-nilai."""
    var = data["var"][0]["val"]
    turvar = data["turvar"][0]["val"]
    turtahun = data["turtahun"][0]["val"]
    rows = []
    for th in data["tahun"]:
        for v in data["vervar"]:
            # kunci datacontent = vervar + var + turvar + tahun + turtahun
            kunci = f"{v['val']}{var}{turvar}{th['val']}{turtahun}"
            if kunci in data["datacontent"]:
                rows.append((nama_sumber, kunci, str(v["val"]), v["label"], int(th["label"]),
                             str(data["datacontent"][kunci])))
    return rows


def extract_d4():
    rows = []
    for path in sorted((RAW_DIR / "json").glob("D4_produksi_kayu_bulat_*.json")):
        rows += baris_dari_json_bps(json.loads(path.read_text(encoding="utf-8")), path.name)
    return rows


def extract_d5():
    path = RAW_DIR / "csv" / "D5_deforestasi_netto_2013_2022.csv"
    semua = list(csv.reader(open(path, encoding="utf-8-sig")))
    baris_periode = next(r for r in semua if r and r[0].strip() == "Provinsi")
    periode_kolom = {i: v.strip() for i, v in enumerate(baris_periode) if re.match(r"\s*\d{4}-\d{4}", v)}
    rows = []
    for n, r in enumerate(semua, start=1):
        nama = r[0].strip() if r else ""
        if not nama or nama in ("Provinsi",) or nama.lower().startswith(("ket", "sumber", "angka deforestasi")):
            continue
        if len(r) < 4 or not r[1].strip():
            continue
        for i, periode in periode_kolom.items():
            rows.append((path.name, n, nama, periode, r[i], r[i + 1], r[i + 2]))
    return rows


TABEL = {
    "D1": ("stg_d1_tutupan_kelas", extract_d1,
           "sumber_file, baris_ke, kelompok_raw, no_raw, kelas_raw, tahun_data, kawasan_raw, apl_raw, total_raw"),
    "D2": ("stg_d2_tutupan_lahan", extract_d2,
           "sumber_file, blok, baris_ke, provinsi_raw, tahun_data, hutan_raw, hutan_pct_raw, "
           "non_hutan_raw, non_hutan_pct_raw, jumlah_raw"),
    "D3": ("stg_d3_karhutla", extract_d3,
           "sumber_file, baris_ke, kode_wilayah_raw, provinsi_raw, tahun_data, luas_raw"),
    "D4": ("stg_d4_produksi_kayu", extract_d4,
           "sumber_file, datacontent_key, vervar_val, provinsi_raw, tahun_data, nilai_raw"),
    "D5": ("stg_d5_deforestasi", extract_d5,
           "sumber_file, baris_ke, provinsi_raw, periode_raw, dalam_kawasan_raw, luar_kawasan_raw, total_raw"),
}


def muat_staging(conn, dataset, waktu):
    """Baca file sumber satu data lalu ganti isi tabel staging-nya. Dipakai ETL penuh dan sinkronisasi."""
    tabel, fungsi, kolom = TABEL[dataset]
    rows = [r + (waktu,) for r in fungsi()]
    isian = ", ".join(["%s"] * (kolom.count(",") + 2))
    with conn.cursor() as cur:
        # Isi staging diganti penuh pada setiap putaran. Staging tidak ikut direplikasi.
        cur.execute(f"DELETE FROM {DB_STAGING}.{tabel}")
        cur.executemany(f"INSERT INTO {DB_STAGING}.{tabel} ({kolom}, extracted_at) VALUES ({isian})", rows)
    return tabel, len(rows)


def main(rid=None):
    rid = rid or run_id()
    conn = koneksi(DB_STAGING)
    waktu = sekarang()
    try:
        for dataset in TABEL:
            tabel, n = muat_staging(conn, dataset, waktu)
            catat_etl(conn, rid, "extract", dataset, n, n, 0, "OK", f"{n} baris teks mentah -> {DB_STAGING}.{tabel}")
            print(f"[extract] {dataset}: {n} baris -> {tabel}")
        conn.commit()
    finally:
        conn.close()
    return rid


if __name__ == "__main__":
    main()
