"""Demo TRANSFORMATION untuk presentasi: data mentah (staging) vs data bersih (master), berdampingan.

  python scripts/demo_transformasi.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "etl"))
from config import koneksi  # noqa: E402


def tampil(cur, sql):
    cur.execute(sql)
    kepala = [c[0] for c in cur.description]
    baris = [[("" if v is None else str(v)) for v in r] for r in cur.fetchall()]
    lebar = [max(len(k), *(len(b[i]) for b in baris)) if baris else len(k) for i, k in enumerate(kepala)]
    garis = "+" + "+".join("-" * (w + 2) for w in lebar) + "+"
    print(garis)
    print("| " + " | ".join(k.ljust(w) for k, w in zip(kepala, lebar)) + " |")
    print(garis)
    for b in baris:
        print("| " + " | ".join(v.ljust(w) for v, w in zip(b, lebar)) + " |")
    print(garis)


def judul(teks, ket):
    print(f"\n\033[1;32m== {teks}\033[0m\n{ket}")


def lanjut():
    input("\n[Enter] lanjut... ")


def main():
    conn = koneksi()
    cur = conn.cursor()

    judul("T1 + T4: format angka & satuan", "Riau 2019 di tiga sumber: cara menulis angkanya beda-beda, D2 masih dalam RIBU ha.")
    tampil(cur, """
        SELECT 'D2 tutupan hutan' AS data, hutan_raw AS mentah_di_staging, 'ribu ha, spasi = ribuan' AS catatan
          FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw='RIAU' AND tahun_data=2019
        UNION ALL SELECT 'D3 karhutla', luas_raw, 'titik = ribuan, koma = desimal'
          FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw='Riau' AND tahun_data=2019
        UNION ALL SELECT 'D5 deforestasi 2018-2019', total_raw, 'koma = ribuan, titik = desimal'
          FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw='Riau' AND periode_raw LIKE '2018%'""")
    tampil(cur, """
        SELECT 'D2 tutupan hutan' AS data, hutan_ha AS bersih_di_master_ha
          FROM kehutanan_master.fakta_tutupan_lahan WHERE kode_bps='14' AND tahun=2019
        UNION ALL SELECT 'D3 karhutla', luas_ha FROM kehutanan_master.fakta_karhutla WHERE kode_bps='14' AND tahun=2019
        UNION ALL SELECT 'D5 deforestasi 2018-2019', total_ha
          FROM kehutanan_master.fakta_deforestasi WHERE kode_bps='14' AND periode='2018-2019'""")
    lanjut()

    judul("T2: nama provinsi", "Satu provinsi, tiga cara menulis (D4 sama dengan D2). Semuanya diganti kode BPS yang sama (19).")
    tampil(cur, """
        SELECT DISTINCT 'D2' AS data, provinsi_raw AS ditulis_di_sumber FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw LIKE '%BANGKA%'
        UNION SELECT DISTINCT 'D3', provinsi_raw FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw LIKE '%Bangka%'
        UNION SELECT DISTINCT 'D5', provinsi_raw FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw LIKE '%Bangka%'""")
    tampil(cur, "SELECT kode_bps, nama AS di_master FROM kehutanan_master.ref_provinsi WHERE kode_bps='19'")
    lanjut()

    judul("T3: pemekaran Papua", "Sejak 2023 sumber D4 memakai 38 provinsi (Papua dipecah); di master digabung balik ke induknya (34 provinsi).")
    tampil(cur, "SELECT provinsi_raw AS ditulis_di_sumber_2025 FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025 AND provinsi_raw LIKE '%PAPUA%'")
    tampil(cur, """
        SELECT COUNT(DISTINCT provinsi_raw) - 1 AS provinsi_di_staging_2025,
               (SELECT COUNT(*) FROM kehutanan_master.fakta_produksi_kayu WHERE tahun=2025) AS provinsi_di_master_2025
          FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025""")
    lanjut()

    judul("T7: cek total", "Jumlah semua provinsi dibandingkan dengan baris total INDONESIA di file sumber (selisih harus 0%).")
    tampil(cur, """
        SELECT dataset, status, LEFT(COALESCE(substring(pesan from 'Cek total.*'), pesan), 110) AS hasil_cek_total
          FROM kehutanan_master.etl_log
         WHERE tahap='transform-load' AND dataset<>'REF' AND run_id=(SELECT MAX(run_id) FROM kehutanan_master.etl_log)
         ORDER BY dataset""")
    print("\nSelesai. Aturan lengkap T1-T8: docs/NASKAH_DEMO.md (Lampiran A).")
    conn.close()


if __name__ == "__main__":
    main()
