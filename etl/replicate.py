"""REPLICATION: kehutanan_master -> kehutanan_dashboard (replika yang dibaca website).

Replikasi inkremental berbasis primary key, dijalankan setelah setiap putaran ETL / sinkronisasi:
  - baris yang ada di master tapi belum ada di replika  -> disisipkan
  - baris yang isinya berbeda                           -> diperbarui
  - baris yang sudah tidak ada di master                -> dihapus dari replika
lalu jumlah baris dan checksum (MD5 seluruh isi tabel) kedua sisi dibandingkan.

Hasilnya dicatat di kehutanan_master.replication_log, lalu ikut disalin ke replika dan tampil di dashboard.

Catatan Supabase: bila ingin replikasi tingkat server, Supabase menyediakan Read Replica (fitur berbayar) yang menyalin
seluruh database secara fisik. Website cukup diarahkan ke host replica lewat DATA_DB_HOST. Script ini tetap dipakai
sebagai bukti replikasi di tingkat data dan sebagai pembatas: website hanya membaca schema kehutanan_dashboard.
"""
from config import DB_DASHBOARD, DB_MASTER, koneksi, sekarang

TABEL = {
    "ref_provinsi": ("kode_bps",),
    "fakta_tutupan_kelas": ("kode_kelas", "tahun"),
    "fakta_tutupan_lahan": ("kode_bps", "tahun"),
    "fakta_karhutla": ("kode_bps", "tahun"),
    "fakta_produksi_kayu": ("kode_bps", "tahun"),
    "fakta_deforestasi": ("kode_bps", "periode"),
    "etl_log": ("id",),
    "sync_log": ("id",),
}


def _semua_baris(cur, db, tabel, kunci):
    cur.execute(f"SELECT * FROM {db}.{tabel}")
    kolom = [c[0] for c in cur.description]
    idx = [kolom.index(k) for k in kunci]
    return kolom, {tuple(r[i] for i in idx): r for r in cur.fetchall()}


def _hitung(cur, db, tabel, kunci):
    """Jumlah baris + checksum 64-bit dari isi seluruh tabel (urut menurut primary key)."""
    urut = ", ".join(f"t.{k}" for k in kunci)
    cur.execute(
        f"SELECT COUNT(*), COALESCE(('x' || substr(md5(string_agg(t::text, '|' ORDER BY {urut})), 1, 15))::bit(60)::bigint, 0) "
        f"FROM {db}.{tabel} t"
    )
    jumlah, checksum = cur.fetchone()
    return jumlah, checksum


def _catat(cur, tabel, n_master, n_replika, baru, berubah, hilang, cs_master, cs_replika):
    status = "SINKRON" if cs_master == cs_replika and n_master == n_replika else "BEDA"
    cur.execute(
        f"INSERT INTO {DB_MASTER}.replication_log (mode, tabel, baris_master, baris_replika, baris_disisipkan, "
        "baris_diperbarui, baris_dihapus, checksum_master, checksum_replika, status, waktu) "
        "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
        ("script", tabel, n_master, n_replika, baru, berubah, hilang, cs_master, cs_replika, status, sekarang()),
    )
    return status


def salin_tabel(cur, tabel, kunci):
    """Samakan isi replika dengan master (sisipkan / perbarui / hapus)."""
    kolom, master = _semua_baris(cur, DB_MASTER, tabel, kunci)
    _, replika = _semua_baris(cur, DB_DASHBOARD, tabel, kunci)

    baru = [master[k] for k in master.keys() - replika.keys()]
    berubah = [master[k] for k in master.keys() & replika.keys() if master[k] != replika[k]]
    hilang = list(replika.keys() - master.keys())

    daftar_kolom = ", ".join(kolom)
    isian = ", ".join(["%s"] * len(kolom))
    nilai = [k for k in kolom if k not in kunci]
    if baru or berubah:
        sql = f"INSERT INTO {DB_DASHBOARD}.{tabel} ({daftar_kolom}) VALUES ({isian}) ON CONFLICT ({', '.join(kunci)}) "
        sql += ("DO UPDATE SET " + ", ".join(f"{k}=EXCLUDED.{k}" for k in nilai)) if nilai else "DO NOTHING"
        cur.executemany(sql, baru + berubah)
    if hilang:
        syarat = " AND ".join(f"{k}=%s" for k in kunci)
        cur.executemany(f"DELETE FROM {DB_DASHBOARD}.{tabel} WHERE {syarat}", hilang)
    return len(baru), len(berubah), len(hilang)


def main():
    conn = koneksi()
    try:
        with conn.cursor() as cur:
            for tabel, kunci in TABEL.items():
                baru, berubah, hilang = salin_tabel(cur, tabel, kunci)
                (n_m, cs_m), (n_r, cs_r) = _hitung(cur, DB_MASTER, tabel, kunci), _hitung(cur, DB_DASHBOARD, tabel, kunci)
                status = _catat(cur, tabel, n_m, n_r, baru, berubah, hilang, cs_m, cs_r)
                print(f"[replicate] {tabel:22} master={n_m:4}  +{baru:<4} ~{berubah:<4} -{hilang:<4} {status}")
            salin_tabel(cur, "replication_log", ("id",))  # catatan replikasi ikut disalin
        conn.commit()
    finally:
        conn.close()


if __name__ == "__main__":
    main()
