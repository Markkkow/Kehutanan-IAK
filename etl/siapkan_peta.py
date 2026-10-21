"""Ubah GeoJSON provinsi (data/raw/geo) menjadi path SVG ringan untuk peta sebaran di web.

Sumber: github.com/ans-4175/peta-indonesia-geojson (MIT), batas BAKOSURTANAL/Dukcapil, 34 provinsi.
Kode Papua di sumber memakai kode Kemendagri (91 Papua, 92 Papua Barat) -> diseragamkan ke kode BPS kita
(94 Papua, 91 Papua Barat).

  python siapkan_peta.py   ->  web/public/peta/provinsi.json
"""
import json
import math

from config import RAW_DIR, ROOT

KE_KODE_BPS = {91: "94", 92: "91"}
LON0, LAT0, SKALA = 94.8, 6.4, 22.0  # proyeksi sederhana: x = (lon - LON0) * SKALA, y = (LAT0 - lat) * SKALA
TOLERANSI = 0.03                       # derajat (~3 km) untuk penyederhanaan garis
LUAS_MIN = 0.004                       # pulau sangat kecil (derajat persegi) dibuang, kecuali poligon terbesar


def jarak_ke_garis(p, a, b):
    if a == b:
        return math.dist(p, a)
    (x, y), (x1, y1), (x2, y2) = p, a, b
    return abs((y2 - y1) * x - (x2 - x1) * y + x2 * y1 - y2 * x1) / math.dist(a, b)


def sederhanakan(titik, tol):
    """Douglas-Peucker."""
    if len(titik) < 3:
        return titik
    idx, maks = 0, 0.0
    for i in range(1, len(titik) - 1):
        d = jarak_ke_garis(titik[i], titik[0], titik[-1])
        if d > maks:
            idx, maks = i, d
    if maks <= tol:
        return [titik[0], titik[-1]]
    return sederhanakan(titik[: idx + 1], tol)[:-1] + sederhanakan(titik[idx:], tol)


def luas(cincin):
    return abs(sum(x1 * y2 - x2 * y1 for (x1, y1), (x2, y2) in zip(cincin, cincin[1:]))) / 2


def ke_path(poligon):
    bagian = []
    for cincin in poligon[:1]:  # cincin luar saja; lubang (danau) diabaikan pada skala ini
        t = sederhanakan([tuple(p[:2]) for p in cincin], TOLERANSI)
        if len(t) < 4:
            continue
        xy = [((lon - LON0) * SKALA, (LAT0 - lat) * SKALA) for lon, lat in t]
        bagian.append("M" + "L".join(f"{x:.1f},{y:.1f}" for x, y in xy) + "Z")
    return "".join(bagian)


def main():
    g = json.loads((RAW_DIR / "geo" / "indonesia-prov.geojson").read_text(encoding="utf-8"))
    hasil = []
    for f in g["features"]:
        kode = KE_KODE_BPS.get(f["properties"]["kode"], str(f["properties"]["kode"]))
        geom = f["geometry"]
        poligon = [geom["coordinates"]] if geom["type"] == "Polygon" else geom["coordinates"]
        poligon.sort(key=lambda p: luas(p[0]), reverse=True)
        dipakai = [p for i, p in enumerate(poligon) if i == 0 or luas(p[0]) >= LUAS_MIN]
        hasil.append({"kode": kode, "d": "".join(ke_path(p) for p in dipakai)})

    keluar = ROOT / "web" / "public" / "peta" / "provinsi.json"
    lebar, tinggi = round((141.2 - LON0) * SKALA), round((LAT0 + 11.2) * SKALA)
    keluar.write_text(json.dumps({"viewBox": f"0 0 {lebar} {tinggi}", "provinsi": hasil}, separators=(",", ":")), encoding="utf-8")
    print(f"{len(hasil)} provinsi -> {keluar} ({keluar.stat().st_size / 1024:.0f} KB)")


if __name__ == "__main__":
    main()
