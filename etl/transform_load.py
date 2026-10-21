"""TRANSFORM + LOAD: kehutanan_staging (teks mentah) -> kehutanan_master (data bersih).

Aturan transformasi utama:
  T1  Format angka diseragamkan (gaya Indonesia, Inggris, spasi) -> float           (angka.py)
  T2  Nama provinsi beragam -> kode BPS 2 digit; baris total nasional dipisahkan      (provinsi.py)
  T3  Provinsi hasil pemekaran Papua 2022 digabung ke induknya -> standar 34 provinsi
  T4  Satuan diseragamkan ke hektar (D1 & D2 tertulis ribu ha)
  T5  Struktur tabel lebar / header bertingkat -> format panjang (provinsi/kelas, tahun, nilai)
  T6  Filter periode 2018-2025; khusus D3, 2026 (baru Januari-Juli) tetap disimpan sebagai data parsial
  T7  Cek kualitas: jumlah rincian (provinsi/kelas) dibandingkan dengan baris total di sumber
  T8  D1: nama kelas dibersihkan & dikelompokkan (hutan primer/sekunder/tanaman, non-hutan), lalu total hutan
      nasional dicocokkan silang dengan baris total D2 (keduanya dari buku Rekalkulasi Penutupan Lahan KLHK)
Load memakai UPSERT (insert kalau belum ada, update kalau sudah ada).
"""
import re
from collections import defaultdict

from angka import angka_indonesia, angka_inggris, angka_polos, angka_spasi
from config import DB_MASTER, DB_STAGING, TAHUN_MAKS, TAHUN_MIN, catat_etl, koneksi, run_id, sekarang
from provinsi import PROVINSI, kode_provinsi

CATATAN_KALTIM = "Termasuk Kalimantan Utara"


def ambil(conn, sql):
    with conn.cursor() as cur:
        cur.execute(sql)
        kolom = [c[0] for c in cur.description]
        return [dict(zip(kolom, r)) for r in cur.fetchall()]


def upsert(conn, tabel, kolom_kunci, baris):
    if not baris:
        return 0
    kolom = list(baris[0].keys())
    isian = ", ".join(["%s"] * len(kolom))
    nilai = [k for k in kolom if k not in kolom_kunci and k != "updated_at"]
    # updated_at hanya berubah bila datanya berubah (ekspresi SET membaca baris lama, jadi urutan tidak berpengaruh)
    sama = " AND ".join(f"t.{k} IS NOT DISTINCT FROM EXCLUDED.{k}" for k in nilai)
    perbarui = ", ".join([f"updated_at = CASE WHEN {sama} THEN t.updated_at ELSE EXCLUDED.updated_at END"] +
                         [f"{k} = EXCLUDED.{k}" for k in nilai])
    sql = (f"INSERT INTO {DB_MASTER}.{tabel} AS t ({', '.join(kolom)}) VALUES ({isian}) "
           f"ON CONFLICT ({', '.join(kolom_kunci)}) DO UPDATE SET {perbarui}")
    with conn.cursor() as cur:
        cur.executemany(sql, [tuple(b[k] for k in kolom) for b in baris])
    return len(baris)


def hapus_usang(conn, tabel, kolom_kunci, baris):
    """Hapus baris master yang tidak lagi dihasilkan transformasi (mis. data yang kini dinyatakan tidak valid)."""
    kunci_baru = {tuple(b[k] for k in kolom_kunci) for b in baris}
    with conn.cursor() as cur:
        cur.execute(f"SELECT {', '.join(kolom_kunci)} FROM {DB_MASTER}.{tabel}")
        usang = [k for k in cur.fetchall() if k not in kunci_baru]
        if usang:
            syarat = " AND ".join(f"{k}=%s" for k in kolom_kunci)
            cur.executemany(f"DELETE FROM {DB_MASTER}.{tabel} WHERE {syarat}", usang)
    return len(usang)


def cek_total(nama, per_kunci, total_sumber, toleransi=0.5):
    """Bandingkan jumlah semua provinsi dengan baris total di sumber (selisih dalam persen)."""
    hasil = []
    for kunci, total in sorted(total_sumber.items()):
        jumlah = per_kunci.get(kunci, 0)
        selisih = 0 if not total else abs(jumlah - total) / total * 100
        status = "OK" if selisih <= toleransi else "BEDA"
        hasil.append(f"{kunci}: {status} (selisih {selisih:.2f}%)")
    return f"Cek total {nama} -> " + "; ".join(hasil)


def _tambah(agregat, kunci, nilai):
    for k, v in nilai.items():
        if v is not None:
            agregat[kunci][k] = (agregat[kunci].get(k) or 0) + v


# ---------------------------------------------------------------- D1
def jenis_hutan(kelas):
    """T8: kelas hutan dikelompokkan menurut asal-usulnya."""
    for kata in ("primer", "sekunder", "tanaman"):
        if kata in kelas.lower():
            return kata
    return None


def transform_d1(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d1_tutupan_kelas")
    hasil, dibuang = [], defaultdict(int)
    jumlah = defaultdict(lambda: defaultdict(float))  # tahun -> {hutan, non_hutan, semua}
    total = defaultdict(dict)                          # baris jumlah di sumber: tahun -> {hutan, non_hutan, semua}
    for r in rows:
        tahun, nama = r["tahun_data"], re.sub(r"\s*\*$", "", r["kelas_raw"]).strip()  # "Hutan tanaman *" -> "Hutan tanaman"
        # T4: tabel tertulis ribu ha
        kawasan, apl, tot = (None if v is None else round(v * 1000, 2)
                             for v in (angka_polos(r[k]) for k in ("kawasan_raw", "apl_raw", "total_raw")))
        if not r["no_raw"]:  # T7: baris "Jumlah Hutan", "Jumlah Non Hutan", "Total" dipisah untuk cek total
            kunci = {"jumlah hutan": "hutan", "jumlah non hutan": "non_hutan", "total": "semua"}.get(nama.lower())
            if kunci:
                total[kunci][tahun] = tot
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        kelompok = "hutan" if "non" not in (r["kelompok_raw"] or "").lower() else "non_hutan"
        hasil.append({
            "kode_kelas": int(float(r["no_raw"])), "tahun": tahun, "kelas": nama, "kelompok": kelompok,
            "jenis_hutan": jenis_hutan(nama) if kelompok == "hutan" else None,
            "kawasan_ha": kawasan, "apl_ha": apl, "total_ha": tot, "updated_at": sekarang(),
        })
        jumlah[kelompok][tahun] += tot or 0
        jumlah["semua"][tahun] += tot or 0

    dalam = lambda d: {t: v for t, v in d.items() if TAHUN_MIN <= t <= TAHUN_MAKS}
    pesan = " | ".join(cek_total(f"D1 {k}", jumlah[k], dalam(total[k])) for k in ("hutan", "non_hutan", "semua"))

    # T8: validasi silang dengan baris total nasional D2 (tabel BPS lain, sumber buku yang sama)
    d2 = {r["tahun_data"]: angka_spasi(r["hutan_raw"]) * 1000
          for r in ambil(conn, f"SELECT tahun_data, provinsi_raw, hutan_raw FROM {DB_STAGING}.stg_d2_tutupan_lahan")
          if kode_provinsi(r["provinsi_raw"]) == "TOTAL" and angka_spasi(r["hutan_raw"]) is not None}
    pesan += " | " + cek_total("silang D1 vs D2 (total hutan)", {t: d2[t] for t in jumlah["hutan"] if t in d2},
                               {t: v for t, v in jumlah["hutan"].items() if t in d2})
    return "fakta_tutupan_kelas", ("kode_kelas", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D2
def transform_d2(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d2_tutupan_lahan")
    agregat, dibuang, total, persen_sumber = defaultdict(dict), defaultdict(int), {}, {}
    for r in rows:
        tahun, kode = r["tahun_data"], kode_provinsi(r["provinsi_raw"])
        hutan, non, jml = (angka_spasi(r[k]) for k in ("hutan_raw", "non_hutan_raw", "jumlah_raw"))
        if kode == "TOTAL":
            total[tahun] = jml * 1000
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        if kode == "65":
            # BPS menggabung Kaltara ke Kaltim untuk 2014-2020 (ada keterangan di tabel); 2021+ sudah terpisah
            # tapi ikut digabung di sini agar deret waktu Kaltim tidak turun palsu di 2021
            kode = "64"
        # T4: ribu ha -> ha
        _tambah(agregat, (kode, tahun), {"hutan_ha": hutan * 1000, "non_hutan_ha": non * 1000, "total_ha": jml * 1000})
        # persen hutan dipakai apa adanya dari kolom % tabel BPS; bila dua baris digabung (Kaltim + Kaltara) dihitung ulang
        persen_sumber[(kode, tahun)] = None if (kode, tahun) in persen_sumber else angka_spasi(r["hutan_pct_raw"])

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({
            "kode_bps": kode, "tahun": tahun, "hutan_ha": round(v["hutan_ha"], 2),
            "non_hutan_ha": round(v["non_hutan_ha"], 2), "total_ha": round(v["total_ha"], 2),
            "persen_hutan": persen_sumber.get((kode, tahun))
                            or (round(v["hutan_ha"] / (v["hutan_ha"] + v["non_hutan_ha"]) * 100, 2) if v["hutan_ha"] else None),
            "catatan": CATATAN_KALTIM if kode == "64" else None, "updated_at": sekarang(),
        })
        jumlah[tahun] += v["total_ha"]
    total = {t: v for t, v in total.items() if TAHUN_MIN <= t <= TAHUN_MAKS}
    pesan = cek_total("D2", jumlah, total)
    return "fakta_tutupan_lahan", ("kode_bps", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D3
def transform_d3(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d3_karhutla")
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        tahun, kode, luas = r["tahun_data"], kode_provinsi(r["provinsi_raw"]), angka_indonesia(r["luas_raw"])
        if kode == "TOTAL":
            total[tahun] = luas
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        # tahun berjalan (TAHUN_MAKS + 1, baru s.d. Juli) tetap dimuat; web hanya memakainya di peta & peringkat, bukan tren
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS + 1):
            dibuang["di luar periode"] += 1
            continue
        _tambah(agregat, (kode, tahun), {"luas_ha": luas})

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "tahun": tahun, "luas_ha": round(v.get("luas_ha", 0), 2),
                      "updated_at": sekarang()})
        jumlah[tahun] += v.get("luas_ha", 0)
    total = {t: v for t, v in total.items() if TAHUN_MIN <= t <= TAHUN_MAKS + 1}
    return "fakta_karhutla", ("kode_bps", "tahun"), hasil, len(rows), dibuang, cek_total("D3", jumlah, total)


# ---------------------------------------------------------------- D4
def transform_d4_rows(rows):
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        tahun, kode, nilai = r["tahun_data"], kode_provinsi(r["provinsi_raw"]), angka_polos(r["nilai_raw"])
        if kode == "TOTAL":
            total[tahun] = nilai
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        _tambah(agregat, (kode, tahun), {"produksi_m3": nilai})

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "tahun": tahun, "produksi_m3": round(v.get("produksi_m3", 0), 2),
                      "updated_at": sekarang()})
        jumlah[tahun] += v.get("produksi_m3", 0)
    return hasil, dibuang, cek_total("D4", jumlah, total)


def transform_d4(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d4_produksi_kayu")
    hasil, dibuang, pesan = transform_d4_rows(rows)
    return "fakta_produksi_kayu", ("kode_bps", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D5
def transform_d5(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d5_deforestasi")
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        kode = kode_provinsi(r["provinsi_raw"])
        awal, akhir = (int(x) for x in re.findall(r"\d{4}", r["periode_raw"]))
        periode = f"{awal}-{akhir}"
        dalam, luar, tot = (angka_inggris(r[k]) for k in ("dalam_kawasan_raw", "luar_kawasan_raw", "total_raw"))
        if kode == "TOTAL":
            total[periode] = tot
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if akhir < TAHUN_MIN:
            dibuang["periode sebelum 2018"] += 1
            continue
        _tambah(agregat, (kode, periode, awal, akhir),
                {"dalam_kawasan_ha": dalam, "luar_kawasan_ha": luar, "total_ha": tot})

    hasil, jumlah = [], defaultdict(float)
    for (kode, periode, awal, akhir), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "periode": periode, "tahun_awal": awal, "tahun_akhir": akhir,
                      "dalam_kawasan_ha": v.get("dalam_kawasan_ha"), "luar_kawasan_ha": v.get("luar_kawasan_ha"),
                      "total_ha": v.get("total_ha"), "updated_at": sekarang()})
        jumlah[periode] += v.get("total_ha") or 0
    total = {p: v for p, v in total.items() if int(p[-4:]) >= TAHUN_MIN}
    return "fakta_deforestasi", ("kode_bps", "periode"), hasil, len(rows), dibuang, cek_total("D5", jumlah, total)


TRANSFORM = {"D1": transform_d1, "D2": transform_d2, "D3": transform_d3, "D4": transform_d4, "D5": transform_d5}


def load_provinsi(conn):
    baris = [{"kode_bps": k, "nama": n, "wilayah": w, "updated_at": sekarang()} for k, n, w in PROVINSI]
    return upsert(conn, "ref_provinsi", ("kode_bps",), baris)


def main(rid=None):
    rid = rid or run_id()
    conn = koneksi(DB_MASTER)
    try:
        n = load_provinsi(conn)
        catat_etl(conn, rid, "load", "REF", n, n, 0, "OK", "34 provinsi standar (kode BPS)")
        for dataset, fungsi in TRANSFORM.items():
            tabel, kunci, hasil, masuk, dibuang, pesan = fungsi(conn)
            dimuat = upsert(conn, tabel, kunci, hasil)
            terhapus = hapus_usang(conn, tabel, kunci, hasil)
            if terhapus:
                pesan += f" | {terhapus} baris lama dihapus dari master"
            alasan = "; ".join(f"{k}: {v}" for k, v in dibuang.items()) or "-"
            status = "PERIKSA" if "BEDA" in pesan else "OK"
            catat_etl(conn, rid, "transform-load", dataset, masuk, dimuat, sum(dibuang.values()), status,
                      f"Dibuang -> {alasan} | {pesan}")
            print(f"[transform-load] {dataset}: {masuk} baris staging -> {dimuat} baris {tabel} [{status}]")
            print(f"    dibuang: {alasan}")
            print(f"    {pesan}")
        conn.commit()
    finally:
        conn.close()
    return rid


if __name__ == "__main__":
    main()
