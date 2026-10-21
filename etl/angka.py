"""Pengubah teks angka ke float. Tiap sumber memakai gaya penulisan angka yang berbeda."""

KOSONG = {"", "-", "–", "—", "...", "…", "nan", "none", "na"}


def _kosong(teks):
    return teks is None or str(teks).strip().lower() in KOSONG


def angka_polos(teks):
    """'1781677.92' -> 1781677.92  (D1, D4)"""
    if _kosong(teks):
        return None
    return float(str(teks).strip())


def angka_indonesia(teks):
    """'1.284,70' -> 1284.70  (D3 SiPongi: titik = ribuan, koma = desimal)"""
    if _kosong(teks):
        return None
    return float(str(teks).strip().replace(".", "").replace(",", "."))


def angka_inggris(teks):
    """'3,363.9' -> 3363.9  (D5: koma = ribuan, titik = desimal)"""
    if _kosong(teks):
        return None
    return float(str(teks).strip().replace(",", ""))


def angka_spasi(teks):
    """'3 156.7' -> 3156.7  (D2: spasi = ribuan, titik = desimal)"""
    if _kosong(teks):
        return None
    return float(str(teks).replace(" ", "").replace("\xa0", "").strip())
