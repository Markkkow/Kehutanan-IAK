"""Standar wilayah: 34 provinsi dengan kode BPS (batas wilayah sebelum pemekaran Papua 2022)."""
import re

PROVINSI = [
    ("11", "Aceh", "Sumatera"),
    ("12", "Sumatera Utara", "Sumatera"),
    ("13", "Sumatera Barat", "Sumatera"),
    ("14", "Riau", "Sumatera"),
    ("15", "Jambi", "Sumatera"),
    ("16", "Sumatera Selatan", "Sumatera"),
    ("17", "Bengkulu", "Sumatera"),
    ("18", "Lampung", "Sumatera"),
    ("19", "Kepulauan Bangka Belitung", "Sumatera"),
    ("21", "Kepulauan Riau", "Sumatera"),
    ("31", "DKI Jakarta", "Jawa"),
    ("32", "Jawa Barat", "Jawa"),
    ("33", "Jawa Tengah", "Jawa"),
    ("34", "DI Yogyakarta", "Jawa"),
    ("35", "Jawa Timur", "Jawa"),
    ("36", "Banten", "Jawa"),
    ("51", "Bali", "Bali & Nusa Tenggara"),
    ("52", "Nusa Tenggara Barat", "Bali & Nusa Tenggara"),
    ("53", "Nusa Tenggara Timur", "Bali & Nusa Tenggara"),
    ("61", "Kalimantan Barat", "Kalimantan"),
    ("62", "Kalimantan Tengah", "Kalimantan"),
    ("63", "Kalimantan Selatan", "Kalimantan"),
    ("64", "Kalimantan Timur", "Kalimantan"),
    ("65", "Kalimantan Utara", "Kalimantan"),
    ("71", "Sulawesi Utara", "Sulawesi"),
    ("72", "Sulawesi Tengah", "Sulawesi"),
    ("73", "Sulawesi Selatan", "Sulawesi"),
    ("74", "Sulawesi Tenggara", "Sulawesi"),
    ("75", "Gorontalo", "Sulawesi"),
    ("76", "Sulawesi Barat", "Sulawesi"),
    ("81", "Maluku", "Maluku"),
    ("82", "Maluku Utara", "Maluku"),
    ("91", "Papua Barat", "Papua"),
    ("94", "Papua", "Papua"),
]

# Nama ditulis berbeda-beda antarsumber; provinsi hasil pemekaran 2022 digabung ke induknya
ALIAS = {
    "bangka belitung": "19",
    "yogyakarta": "34",
    "daerah istimewa yogyakarta": "34",
    "di yogyakarta": "34",
    "kalimantan timur dan kalimantan utara": "64",
    "papua barat daya": "91",
    "papua selatan": "94",
    "papua tengah": "94",
    "papua pegunungan": "94",
}

BARIS_TOTAL = {"indonesia", "total"}

_KODE_DARI_NAMA = {nama.lower(): kode for kode, nama, _ in PROVINSI}


def bersihkan_nama(nama):
    """'Jawa Barat2' -> 'jawa barat', 'KEP. RIAU' -> 'kep riau'. Angka di akhir = catatan kaki."""
    if nama is None:
        return ""
    s = str(nama).strip().lower()
    s = re.sub(r"\d+$", "", s)
    s = s.replace(".", " ")
    s = re.sub(r"\s+", " ", s).strip()
    return s


def kode_provinsi(nama):
    """Kembalikan kode BPS 2 digit, 'TOTAL' untuk baris nasional, atau None bila tidak dikenal."""
    s = bersihkan_nama(nama)
    if s in BARIS_TOTAL:
        return "TOTAL"
    if s.startswith("kep "):
        s = "kepulauan " + s[4:]
    return ALIAS.get(s) or _KODE_DARI_NAMA.get(s)
