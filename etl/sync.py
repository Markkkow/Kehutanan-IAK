"""SYNCHRONIZATION (one-way): file sumber D1-D5 -> kehutanan_master -> (replikasi) -> replika.

Sumber kebenaran tiap data adalah filenya di data/raw. Bila penerbit memperbarui angka (BPS merevisi tabel,
SiPongi menambah data bulan baru), file baru cukup diunduh ulang ke folder yang sama lalu script ini dijalankan.

Beda dengan ETL penuh (run_etl.py) yang memproses semuanya dari awal, sinkronisasi hanya menerapkan SELISIH:
  1. file sumber terbaru dibaca dengan aturan transformasi yang sama (T1-T8),
  2. hasilnya dibandingkan dengan isi master baris per baris (per primary key),
  3. baris baru disisipkan, baris yang nilainya berubah diperbarui, baris yang sudah tidak ada di sumber dihapus;
     baris yang sama tidak disentuh,
  4. hasilnya dicatat di sync_log, lalu replikasi meneruskannya ke replika.
Arahnya selalu satu: sumber -> master. Perubahan di master tidak pernah ditulis balik ke file sumber.

Pemakaian:
  python sync.py                 # sinkronkan D1-D5
  python sync.py D2 D4           # hanya data tertentu
  python sync.py --simulasi D2   # demo: ubah 1 nilai di master dulu, lalu lihat sinkronisasi memperbaikinya
"""
import json
import sys
from decimal import Decimal

import extract
import replicate
from config import DB_MASTER, koneksi, sekarang
from provinsi import PROVINSI
from transform_load import TRANSFORM, upsert

NAMA_PROVINSI = {k: n for k, n, _ in PROVINSI}

# demo --simulasi: satu nilai per data yang diubah jadi 0 di master
SIMULASI = {
    "D1": ("fakta_tutupan_kelas", "total_ha", "kode_kelas = 11 AND tahun = 2023", "luas perkebunan 2023"),
    "D2": ("fakta_tutupan_lahan", "hutan_ha", "kode_bps = '14' AND tahun = 2023", "tutupan hutan Riau 2023"),
    "D3": ("fakta_karhutla", "luas_ha", "kode_bps = '14' AND tahun = 2019", "karhutla Riau 2019"),
    "D4": ("fakta_produksi_kayu", "produksi_m3", "kode_bps = '14' AND tahun = 2025", "produksi kayu Riau 2025"),
    "D5": ("fakta_deforestasi", "total_ha", "kode_bps = '14' AND periode = '2018-2019'", "deforestasi Riau 2018-2019"),
}


def _angka(v):
    return float(v) if isinstance(v, (int, float, Decimal)) and not isinstance(v, bool) else v


def _sama(a, b):
    """Nilai dari file (float) vs dari database (Decimal 2 desimal)."""
    a, b = _angka(a), _angka(b)
    if isinstance(a, float) and isinstance(b, float):
        return round(a, 2) == round(b, 2)
    return a == b


def _label(kunci, baris):
    """Nama baris yang mudah dibaca untuk catatan, mis. 'Riau 2025' atau 'Perkebunan 2023'."""
    if "kode_bps" in kunci:
        return f"{NAMA_PROVINSI.get(baris['kode_bps'], baris['kode_bps'])} {baris.get('tahun') or baris.get('periode')}"
    return f"{baris.get('kelas', baris.get('kode_kelas'))} {baris['tahun']}"


def sinkronkan(conn, dataset):
    tabel, kunci, hasil, *_ = TRANSFORM[dataset](conn)
    kolom = [k for k in hasil[0] if k != "updated_at"]
    with conn.cursor() as cur:
        cur.execute(f"SELECT {', '.join(kolom)} FROM {DB_MASTER}.{tabel}")
        di_master = {}
        for r in cur.fetchall():
            baris = dict(zip(kolom, r))
            di_master[tuple(baris[k] for k in kunci)] = baris

    baru, berubah, detail, sama, ada = [], [], [], 0, set()
    for b in hasil:
        k = tuple(b[x] for x in kunci)
        ada.add(k)
        lama = di_master.get(k)
        if lama is None:
            baru.append(b)
            detail.append({"baris": _label(kunci, b), "jenis": "baru"})
            continue
        beda = [c for c in kolom if c not in kunci and not _sama(b[c], lama[c])]
        if beda:
            berubah.append(b)
            c = beda[0]
            detail.append({"baris": _label(kunci, b), "jenis": "berubah", "kolom": c,
                           "lama": _angka(lama[c]), "baru": _angka(b[c])})
        else:
            sama += 1
    hilang = [k for k in di_master if k not in ada]
    for k in hilang:
        detail.append({"baris": _label(kunci, di_master[k]), "jenis": "dihapus"})

    upsert(conn, tabel, kunci, baru + berubah)
    if hilang:
        syarat = " AND ".join(f"{x}=%s" for x in kunci)
        with conn.cursor() as cur:
            cur.executemany(f"DELETE FROM {DB_MASTER}.{tabel} WHERE {syarat}", hilang)
    return {"dicek": len(hasil), "baru": len(baru), "berubah": len(berubah), "dihapus": len(hilang), "sama": sama,
            "detail": detail[:20]}


def simulasi(conn, dataset):
    tabel, kolom, syarat, nama = SIMULASI[dataset]
    with conn.cursor() as cur:
        cur.execute(f"UPDATE {DB_MASTER}.{tabel} SET {kolom} = 0, updated_at = %s WHERE {syarat}", (sekarang(),))
    conn.commit()
    replicate.main()
    print(f"[simulasi] {dataset}: {nama} di master diubah jadi 0 dan sudah sampai ke replika.")
    input("[simulasi] Cek dashboard (angkanya berubah), lalu tekan Enter untuk menjalankan sinkronisasi... ")


def main():
    argumen = [a.upper() for a in sys.argv[1:] if not a.startswith("--")]
    pilihan = [d for d in TRANSFORM if not argumen or d in argumen]
    conn = koneksi(DB_MASTER)
    try:
        if "--simulasi" in sys.argv:
            simulasi(conn, pilihan[0] if argumen else "D4")
        waktu = sekarang()
        for dataset in pilihan:
            extract.muat_staging(conn, dataset, waktu)  # baca file sumber terbaru
            h = sinkronkan(conn, dataset)
            with conn.cursor() as cur:
                cur.execute(
                    f"INSERT INTO {DB_MASTER}.sync_log (dataset, baris_dicek, baris_baru, baris_berubah, baris_dihapus, baris_sama, "
                    "detail, waktu) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)",
                    (dataset, h["dicek"], h["baru"], h["berubah"], h["dihapus"], h["sama"], json.dumps(h["detail"]), waktu),
                )
            print(f"[sync] {dataset}: dicek {h['dicek']} | baru {h['baru']} | berubah {h['berubah']} | "
                  f"dihapus {h['dihapus']} | sama {h['sama']}")
            for d in h["detail"]:
                print(f"       {d['jenis']:8} {d['baris']}" + (f" ({d['kolom']}: {d['lama']} -> {d['baru']})" if d["jenis"] == "berubah" else ""))
        conn.commit()
    finally:
        conn.close()
    replicate.main()


if __name__ == "__main__":
    main()
