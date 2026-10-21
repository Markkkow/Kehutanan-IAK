/*
 * Dashboard Kehutanan — semua interaksi & grafik (Chart.js). Data dari window.DATA (diisi DashboardController).
 *
 * Aturan visual: satu sumbu Y per grafik (satuan berbeda dibandingkan lewat indeks 2018 = 100); warna = identitas data
 * dan konsisten (primer emerald tua, tutupan hijau daun, karhutla merah-oranye bara, kayu cokelat jati, deforestasi amber-okre,
 * lolos uji buta warna, dengan versi khusus tema gelap); abu-abu untuk konteks; garis lurus untuk data tahunan;
 * label langsung hanya di titik penting; setiap grafik punya tabel data pendamping. Grafik baru dibuat saat panelnya terlihat (lazy).
 */
import Chart from 'chart.js/auto';

const D = window.DATA;
const html = document.documentElement;

// ================================================================== palet per tema
// Palet hutan. Hijau-cokelat & hijau-karat lolos uji buta warna sebagai pasangan; keempat warna data TIDAK pernah
// tampil bersama di satu grafik (tren gabungan dipecah jadi 5 panel kecil).
const TEMA = {
    terang: {
        primer: '#1d6b43', hijau: '#2f7d4f', api: '#c0502e', kayu: '#9c5a1f', amber: '#c58a14', naik: '#2f7d4f', turun: '#c0502e',
        redup: '#e3dccb', abu: '#a3a598', permukaan: '#fffdf8', tinta: '#1e2a1f', tinta2: '#4b5244', tinta3: '#807a66',
        kisi: '#ece5d5', sumbu: '#cfc6b0', tooltip: '#1e3a28',
        ordinal: ['#7fb88a', '#4a9a63', '#1d6b43'],
        ramp: {
            hijau: ['#e2efdc', '#b3d4a8', '#79b07a', '#3f8552', '#1d5634'],
            api: ['#fbe3d3', '#f2b089', '#e07a47', '#c0502e', '#86321a'],
            kayu: ['#f1e5d3', '#dcbb90', '#bd8b56', '#94602d', '#5f3b1a'],
        },
        reforestasi: ['#9fcc9c', '#3f8552'], deforestasi: ['#f6d2bf', '#eaa07c', '#cf6a44', '#9c3d20'], nol: '#efe9da',
    },
    gelap: {
        primer: '#52c788', hijau: '#3a9e6a', api: '#e87052', kayu: '#d49252', amber: '#e6ad3a', naik: '#3a9e6a', turun: '#e87052',
        redup: '#2c3f33', abu: '#5f6b62', permukaan: '#13241b', tinta: '#ecf0e4', tinta2: '#c4ccb8', tinta3: '#8f9b87',
        kisi: '#21352a', sumbu: '#34493c', tooltip: '#0b1610',
        ordinal: ['#2a6e45', '#4f9e68', '#52c788'],
        ramp: {
            hijau: ['#18301f', '#1f4a2e', '#2f6f45', '#56a06a', '#9fd3a8'],
            api: ['#3a2018', '#64301f', '#9f4527', '#d8664a', '#f1a585'],
            kayu: ['#2e2318', '#4d3925', '#7a5634', '#b07a47', '#dcb48a'],
        },
        reforestasi: ['#2f6f45', '#9fd3a8'], deforestasi: ['#4a2418', '#7d3824', '#b9553a', '#eb9a7c'], nol: '#1a2f23',
    },
};
let W = TEMA.terang;
const warnaData = () => ({ primer: W.primer, tutupan: W.hijau, karhutla: W.api, kayu: W.kayu, deforestasi: W.amber });
const RAMP_PETA = { D2: 'hijau', D3: 'api', D4: 'kayu' };

// ================================================================== format angka Indonesia
const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const fmt2 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
const ringkas = (v) => {
    if (v === null || v === undefined || Number.isNaN(v)) return '–';
    const a = Math.abs(v);
    if (a >= 1e6) return fmt.format(v / 1e6) + ' jt';
    if (a >= 1e3) return fmt.format(v / 1e3) + ' rb';
    return fmt.format(v);
};

// ================================================================== gaya dasar Chart.js (diatur ulang saat tema berganti)
function terapkanGayaDasar() {
    W = TEMA[html.dataset.tema === 'terang' ? 'terang' : 'gelap'];
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 11.5;
    Chart.defaults.color = W.tinta3;
    Chart.defaults.borderColor = W.kisi;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.animation.duration = 450;
    Object.assign(Chart.defaults.plugins.legend, { position: 'top', align: 'start' });
    Object.assign(Chart.defaults.plugins.legend.labels, {
        usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, color: W.tinta2, padding: 14,
    });
    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: W.tooltip, titleColor: '#fff', bodyColor: '#e9e8e4', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
        padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true, titleFont: { weight: '600' },
    });
    Chart.defaults.scale.grid.color = W.kisi;
    Chart.defaults.scale.border.color = W.sumbu;
    Object.assign(Chart.defaults.elements.point, { radius: 3.5, hoverRadius: 6, borderWidth: 2, borderColor: W.permukaan, hitRadius: 14 });
    Chart.defaults.elements.line.borderWidth = 2;
}

const sumbuRingkas = () => ({ ticks: { callback: (v) => ringkas(v), maxRotation: 0, maxTicksLimit: 6 }, grid: { color: W.kisi } });
const tanpaKisi = { grid: { display: false } };
const labelTooltip = (satuan) => (c) => {
    const v = c.chart.options.indexAxis === 'y' ? c.parsed.x : c.parsed.y;
    return ` ${c.dataset.label ? c.dataset.label + ': ' : ''}${fmt.format(v)}${satuan ? ' ' + satuan : ''}`;
};
const gayaBatang = { borderRadius: 5, borderSkipped: 'start', maxBarThickness: 30, categoryPercentage: 0.78, barPercentage: 0.86 };
const gayaTumpuk = () => ({ ...gayaBatang, borderColor: W.permukaan, borderWidth: { right: 2, top: 2 }, borderRadius: 0 });
// Penulisan tahun selalu lengkap: 2019, 2018–2019, dan "2026 (Jan–Jul)" untuk tahun yang belum lengkap (dua baris di sumbu)
const PARSIAL = D.indeks.label_tahun ?? {};
const namaTahun = (t) => PARSIAL[t] ?? (/^\d{4}-\d{4}$/.test(String(t)) ? String(t).replace('-', '–') : String(t));
const labelSumbu = (t) => { const n = namaTahun(t); return n.includes('(') ? n.split(' ') : n; };
// garis menuju tahun belum lengkap dibuat putus-putus dan titiknya berlubang
const tandaiParsial = (labels, warna) => {
    const i = labels.findIndex((t) => PARSIAL[t] || String(t).includes('('));
    if (i < 0) return {};
    return {
        segment: { borderDash: (c) => (c.p1DataIndex === i || c.p1DataIndex - c.p0DataIndex > 1 ? [4, 4] : undefined) },
        pointBackgroundColor: labels.map((_, j) => (j === i ? W.permukaan : warna)),
    };
};
const transparan = (warna, a = 0.14) => warna + Math.round(a * 255).toString(16).padStart(2, '0');
// Skala Y mengikuti data, tapi rentangnya minimal `persen` dari nilai tengah: perubahan nyata terlihat,
// perubahan yang sangat kecil (di bawah ±3%) tetap tampak hampir datar.
const skalaPas = (nilai, persen = 0.06) => {
    const v = nilai.filter((x) => x !== null && x !== undefined);
    const lo = Math.min(...v);
    const hi = Math.max(...v);
    const tengah = (lo + hi) / 2;
    const rentang = Math.max(hi - lo, Math.abs(tengah) * persen) * 1.25;
    return { min: tengah - rentang / 2, max: tengah + rentang / 2 };
};
// Celah data (null) disambung garis putus-putus agar terbaca "data tidak tersedia", bukan grafik rusak
// Batang mendatar bernilai negatif: beri ruang di kiri (≈45% rentang) agar label "−3,6 jt" tidak menabrak nama kategori
const ruangNegatif = (nilai, faktor = 0.45) => {
    const min = Math.min(0, ...nilai), max = Math.max(0, ...nilai);
    return min < 0 ? { suggestedMin: min - (max - min) * faktor } : {};
};
const sambungCelah = { spanGaps: true, segment: { borderDash: (c) => (c.p1DataIndex - c.p0DataIndex > 1 ? [4, 4] : undefined) } };

// ================================================================== plugin label langsung (selektif) & garis acuan
Chart.register(
    {
        id: 'labelUjungBatang',
        afterDatasetsDraw(chart, _a, o) {
            if (!o?.aktif) return;
            const { ctx } = chart;
            ctx.save();
            ctx.font = `500 11px ${Chart.defaults.font.family}`;
            ctx.fillStyle = W.tinta2;
            ctx.textBaseline = 'middle';
            const akhir = chart.data.datasets.length - 1;
            const nilaiKe = (i) => (o.total ? chart.data.datasets.reduce((s, ds) => s + (ds.data[i] ?? 0), 0) : chart.data.datasets[akhir].data[i]);
            const elemen = chart.getDatasetMeta(akhir).data;
            // batang tegak di grafik sempit: nilai terbesar diberi label lebih dulu, label yang akan bertabrakan dilewati (angkanya tetap ada di tooltip & tabel)
            const urutan = elemen.map((_e, i) => i);
            if (chart.options.indexAxis !== 'y') urutan.sort((a, b) => (nilaiKe(b) ?? -Infinity) - (nilaiKe(a) ?? -Infinity));
            const terpakai = [];
            urutan.forEach((i) => {
                const el = elemen[i];
                const nilai = nilaiKe(i);
                if (nilai === null || nilai === undefined) return;
                const teks = (o.format ?? ringkas)(nilai);
                if (chart.options.indexAxis === 'y') {
                    ctx.textAlign = nilai < 0 ? 'right' : 'left';
                    ctx.fillText(teks, el.x + (nilai < 0 ? -6 : 6), el.y);
                } else {
                    const lebar = ctx.measureText(teks).width;
                    const kiri = el.x - lebar / 2 - 2;
                    const kanan = el.x + lebar / 2 + 2;
                    const y = el.y - 10;
                    if (terpakai.some((k) => kiri < k.kanan && kanan > k.kiri && Math.abs(y - k.y) < 13)) return;
                    terpakai.push({ kiri, kanan, y });
                    ctx.textAlign = 'center';
                    ctx.fillText(teks, el.x, y);
                }
            });
            ctx.restore();
        },
    },
    {
        id: 'labelUjungGaris',
        afterDatasetsDraw(chart, _a, o) {
            if (!o?.aktif) return;
            const { ctx } = chart;
            ctx.save();
            ctx.font = `600 11px ${Chart.defaults.font.family}`;
            ctx.textBaseline = 'middle';
            ctx.fillStyle = W.tinta2;
            const dipakai = [];
            chart.data.datasets.forEach((ds, i) => {
                const meta = chart.getDatasetMeta(i);
                if (meta.hidden) return;
                const idx = ds.data.map((v, j) => (v === null ? -1 : j)).filter((j) => j >= 0).pop();
                const el = meta.data[idx];
                if (!el) return;
                let y = el.y;
                while (dipakai.some((p) => Math.abs(p - y) < 13)) y += 13;
                dipakai.push(y);
                ctx.fillText(o.teks ? o.teks(ds, ds.data[idx]) : ds.label, el.x + 9, y);
            });
            ctx.restore();
        },
    },
    {
        id: 'garisAcuan',
        beforeDatasetsDraw(chart, _a, o) {
            if (o?.nilai === undefined) return;
            const horizontal = chart.options.indexAxis === 'y';
            const p = (horizontal ? chart.scales.x : chart.scales.y).getPixelForValue(o.nilai);
            const { ctx, chartArea: a } = chart;
            ctx.save();
            ctx.strokeStyle = W.abu;
            ctx.lineWidth = 1.25;
            ctx.beginPath();
            if (horizontal) { ctx.moveTo(p, a.top); ctx.lineTo(p, a.bottom); } else { ctx.moveTo(a.left, p); ctx.lineTo(a.right, p); }
            ctx.stroke();
            ctx.restore();
        },
    },
);

// ================================================================== definisi setiap grafik (id kanvas -> fungsi pembuat config)
const DEF = {};

// ---- ringkasan: sparkline
// kanvas sparkline terlalu pendek untuk tooltip bawaan Chart.js (terpotong), jadi tooltip digambar sebagai elemen melayang
const tooltipSpark = Object.assign(document.createElement('div'), {
    className: 'pointer-events-none fixed z-50 hidden rounded-lg px-2.5 py-1.5 text-xs text-white shadow-xl',
});
document.body.append(tooltipSpark);
window.addEventListener('scroll', () => tooltipSpark.classList.add('hidden'), { passive: true });
function tooltipLuar({ chart, tooltip }) {
    if (!tooltip.opacity) { tooltipSpark.classList.add('hidden'); return; }
    const kotak = chart.canvas.getBoundingClientRect();
    tooltipSpark.style.background = W.tooltip;
    tooltipSpark.innerHTML = `<div class="font-semibold">${tooltip.title.join(' ')}</div><div class="opacity-80">${tooltip.body.flatMap((b) => b.lines).join(' ')}</div>`;
    tooltipSpark.classList.remove('hidden');
    const lebar = tooltipSpark.offsetWidth;
    const x = Math.min(Math.max(8, kotak.left + tooltip.caretX - lebar / 2), window.innerWidth - lebar - 8);
    Object.assign(tooltipSpark.style, { left: `${x}px`, top: `${kotak.top + tooltip.caretY - tooltipSpark.offsetHeight - 10}px` });
}
const SPARK = { primer: 'primer', tutupan: 'tutupan', deforestasi: 'deforestasi', karhutla: 'karhutla', kayu: 'kayu' };
Object.keys(SPARK).forEach((k) => {
    DEF[`spark-${k}`] = () => {
        const deret = D.deret[k];
        const warna = SPARK[k] ? warnaData()[SPARK[k]] : W.hijau;
        return {
            type: 'line',
            data: { labels: Object.keys(deret), datasets: [{ data: Object.values(deret), borderColor: warna, backgroundColor: transparan(warna, 0.2), fill: true, tension: 0, pointRadius: 0, pointHoverRadius: 3.5, borderWidth: 2, ...tandaiParsial(Object.keys(deret), warna) }] },
            options: {
                animation: false,
                layout: { padding: 2 },
                scales: { x: { display: false }, y: { display: false, ...skalaPas(Object.values(deret)) } },
                plugins: { legend: { display: false }, tooltip: { enabled: false, external: tooltipLuar, callbacks: { title: (i) => namaTahun(i[0].label), label: (c) => ringkas(c.parsed.y) } } },
                interaction: { mode: 'index', intersect: false },
            },
        };
    };
});

// ---- ringkasan: indeks gabungan sebagai 5 panel kecil, masing-masing dengan skala sendiri (rentang minimal 20 poin)
const INDEKS = { primer: 'Hutan primer (D1)', tutupan: 'Tutupan hutan (D2)', karhutla: 'Karhutla (D3)', kayu: 'Produksi kayu (D4)', deforestasi: 'Deforestasi netto (D5)' };
Object.entries(INDEKS).forEach(([k, label]) => {
    DEF[`indeks-${k}`] = () => {
        const warna = warnaData()[k];
        const data = D.indeks.deret[label];
        const kasar = skalaPas([...data, 100], 0.2);
        // batas dibulatkan ke kelipatan langkah agar label sumbu tidak saling menempel
        const langkah = [5, 10, 20, 25, 50, 100].find((s) => (kasar.max - kasar.min) / s <= 4) ?? 100;
        const min = Math.max(0, Math.floor(kasar.min / langkah) * langkah);
        const max = Math.ceil(kasar.max / langkah) * langkah;
        return {
            type: 'line',
            data: { labels: D.indeks.tahun, datasets: [{ label, data, borderColor: warna, backgroundColor: transparan(warna, 0.14), fill: 'start', pointBackgroundColor: warna, tension: 0, ...sambungCelah, ...(k === 'karhutla' ? tandaiParsial(D.indeks.tahun, warna) : {}) }] },
            options: {
                layout: { padding: { right: 34, top: 6 } },
                scales: {
                    y: { min, max, ticks: { stepSize: langkah, font: { size: 10 }, callback: (v) => fmt.format(v) } },
                    x: { ...tanpaKisi, ticks: { font: { size: 10 }, autoSkip: false, maxRotation: 0, callback(v) { const t = this.getLabelForValue(v); return k === 'karhutla' ? labelSumbu(t) : String(t); } } },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false, callbacks: { title: (i) => (k === 'karhutla' ? namaTahun(i[0].label) : i[0].label), label: (c) => ` indeks ${c.parsed.y === null ? '–' : fmt.format(c.parsed.y)}` } },
                    garisAcuan: { nilai: 100 },
                    labelUjungGaris: { aktif: true, teks: (_d, v) => fmt.format(v) },
                },
            },
        };
    };
});
document.querySelector('[data-tabel-indeks]').addEventListener('toggle', (e) => {
    if (!e.target.open) return;
    const kepala = Object.keys(D.indeks.deret).map((l) => `<th class="angka">${l}</th>`).join('');
    const baris = D.indeks.tahun.map((t, i) => `<tr><td>${t}</td>${Object.values(D.indeks.deret).map((d) => `<td class="angka">${d[i] === null ? '–' : fmt.format(d[i])}</td>`).join('')}</tr>`);
    e.target.querySelector('.isi').innerHTML = `<table class="tabel-data"><thead><tr><th>Tahun</th>${kepala}</tr></thead><tbody>${baris.join('')}</tbody></table>`;
});

// ---- D1 tutupan menurut kelas (nasional)
DEF.chartKelasTren = () => {
    const k = D.kelas;
    const warna = [W.ordinal[2], W.ordinal[0], W.kayu]; // primer, sekunder, tanaman
    return {
        type: 'line',
        data: { labels: k.tahun, datasets: k.kumulatif.map((j, i) => ({ label: j.label, data: j.data, borderColor: warna[i], pointBackgroundColor: warna[i], tension: 0 })) },
        options: {
            layout: { padding: { right: 128, top: 8 } },
            scales: { y: { ...sumbuRingkas(), ticks: { ...sumbuRingkas().ticks, callback: (v) => (v > 0 ? '+' : '') + ringkas(v) } }, x: tanpaKisi },
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false, callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y > 0 ? '+' : ''}${ringkas(c.parsed.y)} ha` } },
                garisAcuan: { nilai: 0 },
                labelUjungGaris: { aktif: true, teks: (d, v) => `${d.label.replace('Hutan ', '')} ${v > 0 ? '+' : ''}${ringkas(v)}` },
            },
        },
    };
};
DEF.chartKelasPerubahan = () => {
    const pr = D.kelas.perubahan;
    const warna = (p) => (p.kelompok === 'hutan' ? W.primer : W.kayu);
    return {
        type: 'bar',
        data: { labels: pr.map((p) => p.kelas), datasets: [{ label: 'Perubahan (ha)', data: pr.map((p) => p.selisih), backgroundColor: pr.map(warna), ...gayaBatang, borderSkipped: false }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { left: 4, right: 52 } },
            scales: { x: { ...sumbuRingkas(), grace: '12%', ...ruangNegatif(pr.map((p) => p.selisih)) }, y: { ...tanpaKisi, ticks: { autoSkip: false, font: { size: 10.5 } } } },
            plugins: {
                legend: {
                    labels: {
                        generateLabels: () => [['Kelas hutan', W.primer], ['Kelas non-hutan', W.kayu]].map(([text, w]) => ({ text, fillStyle: w, strokeStyle: w, fontColor: W.tinta2, pointStyle: 'rectRounded' })),
                    },
                    onClick: () => {},
                },
                tooltip: { callbacks: { label: (c) => ` ${c.parsed.x > 0 ? '+' : ''}${fmt.format(c.parsed.x)} ha` } },
                garisAcuan: { nilai: 0 },
                labelUjungBatang: { aktif: true, format: (v) => (v > 0 ? '+' : '') + ringkas(v) },
            },
        },
    };
};

// ---- D2 tutupan
DEF.chartTutupanTren = () => {
    const tt = D.tutupan;
    const perTahun = Object.fromEntries(tt.nasional.map((r) => [r.tahun, r]));
    const warna = warnaData().tutupan;
    return {
        type: 'line',
        data: { labels: tt.tahun, datasets: [{ label: 'Tutupan hutan (ha)', data: tt.tahun.map((t) => perTahun[t]?.hutan ?? null), borderColor: warna, backgroundColor: transparan(warna), fill: 'start', tension: 0, pointBackgroundColor: warna, ...sambungCelah }] },
        options: {
            layout: { padding: { right: 72, top: 8 } },
            scales: { y: { ...sumbuRingkas(), grace: '25%' }, x: tanpaKisi },
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false, callbacks: { label: (c) => ` ${ringkas(c.parsed.y)} ha · ${fmt.format(perTahun[c.label].persen)}% daratan` } },
                labelUjungGaris: { aktif: true, teks: (_d, v) => `${ringkas(v)} ha` },
            },
        },
    };
};
DEF.chartTutupanProvinsi = () => {
    const pr = D.tutupan.perubahan;
    return {
        type: 'bar',
        data: { labels: pr.map((p) => p.nama), datasets: [{ label: 'Perubahan (ha)', data: pr.map((p) => p.selisih), backgroundColor: pr.map((p) => (p.selisih < 0 ? W.turun : W.naik)), ...gayaBatang, borderSkipped: false }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { left: 8, right: 56 } },
            scales: { x: { ...sumbuRingkas(), ...ruangNegatif(pr.map((p) => p.selisih)) }, y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, garisAcuan: { nilai: 0 }, labelUjungBatang: { aktif: true } },
        },
    };
};

// ---- D5 deforestasi
DEF.chartDeforTren = () => {
    const df = D.deforestasi;
    return {
        type: 'bar',
        data: {
            labels: df.nasional.map((r) => r.periode),
            datasets: [
                { label: 'Di dalam kawasan hutan', data: df.nasional.map((r) => r.dalam), backgroundColor: W.amber, ...gayaTumpuk(), borderWidth: { top: 2 } },
                { label: 'Di luar kawasan (APL)', data: df.nasional.map((r) => r.luar), backgroundColor: W.redup, ...gayaTumpuk(), borderWidth: { top: 2 }, borderRadius: { topLeft: 5, topRight: 5 } },
            ],
        },
        options: {
            layout: { padding: { top: 16 } },
            scales: { x: { stacked: true, ...tanpaKisi }, y: { stacked: true, ...sumbuRingkas() } },
            plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha/th') } }, labelUjungBatang: { aktif: true, total: true } },
        },
    };
};
DEF.chartDeforProvinsi = () => {
    const top = D.deforestasi.top;
    return {
        type: 'bar',
        data: { labels: top.map((p) => p.nama), datasets: [{ label: 'Deforestasi netto (ha)', data: top.map((p) => p.total), backgroundColor: W.amber, ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 56 } },
            scales: { x: sumbuRingkas(), y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, labelUjungBatang: { aktif: true } },
        },
    };
};

// ---- D3 karhutla & D4 kayu: tren nasional + peringkat provinsi per tahun
const trenNasional = (data, warna, satuan, sorot) => () => {
    const tahun = Object.keys(data.nasional);
    const nilai = Object.values(data.nasional);
    const latar = tahun.map((t) => (!sorot || sorot.includes(Number(t)) ? warna() : W.redup));
    // tahun belum lengkap (mis. 2026 Jan–Jul) ditampilkan pucat dengan tanda * agar tidak terbaca sebagai penurunan
    if (data.parsial) {
        tahun.push(data.parsial.label.split(' '));
        nilai.push(data.parsial.nilai);
        latar.push(transparan(warna(), 0.3));
    }
    return {
        type: 'bar',
        data: { labels: tahun, datasets: [{ label: satuan, data: nilai, backgroundColor: latar, ...gayaBatang }] },
        options: {
            layout: { padding: { top: 18 } },
            scales: { y: sumbuRingkas(), x: { ...tanpaKisi, ticks: { maxRotation: 0, autoSkip: false, font: { size: 10.5 } } } },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { title: (i) => (data.parsial && i[0].dataIndex === tahun.length - 1 ? data.parsial.label : i[0].label), label: labelTooltip(satuan) } },
                labelUjungBatang: { aktif: true },
            },
        },
    };
};
const peringkatProvinsi = (data, warna, satuan, idPilih) => () => {
    const tahunDipilih = Number(document.getElementById(idPilih).value);
    const i = data.tahun.indexOf(tahunDipilih);
    const urut = data.provinsi.map((p) => ({ nama: p.nama, v: p.nilai[i] })).sort((a, b) => b.v - a.v).slice(0, 10);
    return {
        type: 'bar',
        data: { labels: urut.map((p) => p.nama), datasets: [{ label: `${satuan} (${namaTahun(tahunDipilih)})`, data: urut.map((p) => p.v), backgroundColor: warna(), ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 56 } },
            scales: { x: sumbuRingkas(), y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(satuan) } }, labelUjungBatang: { aktif: true } },
        },
    };
};
DEF.chartKarhutlaTren = trenNasional(D.karhutla, () => warnaData().karhutla, 'ha', D.karhutla.sorot);
DEF.chartKarhutlaProvinsi = peringkatProvinsi(D.karhutla, () => warnaData().karhutla, 'ha', 'pilihTahunKarhutla');
DEF.chartKayuTren = trenNasional(D.kayu, () => warnaData().kayu, 'm³');
DEF.chartKayuProvinsi = peringkatProvinsi(D.kayu, () => warnaData().kayu, 'm³', 'pilihTahunKayu');

// ---- G2 kebakaran (D3) vs hutan hilang (D5): satu sumbu hektar. Warna api & amber dibedakan juga lewat
// garis putus-putus dan bentuk titik, agar tetap terbaca oleh pembaca buta warna.
DEF.chartApiHutan = () => {
    const ah = D.apiHutan;
    const api = warnaData().karhutla;
    const hilang = warnaData().deforestasi;
    return {
        type: 'line',
        data: {
            labels: ah.tahun,
            datasets: [
                { label: 'Luas kebakaran (D3)', data: ah.karhutla, borderColor: api, pointBackgroundColor: api, tension: 0, borderWidth: 2.5 },
                { label: 'Deforestasi netto (D5)', data: ah.deforestasi, borderColor: hilang, pointBackgroundColor: hilang, pointStyle: 'rectRot', pointRadius: 5, borderDash: [6, 4], tension: 0, borderWidth: 2.5 },
            ],
        },
        options: {
            layout: { padding: { right: 118, top: 8 } },
            scales: { y: { ...sumbuRingkas(), beginAtZero: true, title: { display: true, text: 'hektar', color: W.tinta3 } }, x: tanpaKisi },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index', intersect: false,
                    callbacks: { label: (c) => (c.datasetIndex === 0 ? ` Kebakaran ${c.label}: ${ringkas(c.parsed.y)} ha` : ` Deforestasi ${ah.periode[c.dataIndex]}: ${ringkas(c.parsed.y)} ha/th`) },
                },
                labelUjungGaris: { aktif: true, teks: (d) => (d.label.startsWith('Luas') ? 'kebakaran' : 'deforestasi') },
            },
        },
    };
};

// ---- G3 benang merah (gelembung)
DEF.chartBenangMerah = () => {
    const bm = D.benangMerah;
    const warnaKelompok = { Sumatera: W.hijau, Kalimantan: W.kayu, 'Wilayah lain': W.abu };
    const maks = Math.max(...bm.titik.map((t) => t.kayu));
    return {
        type: 'bubble',
        data: {
            datasets: bm.wilayah.map((w) => ({
                label: w, backgroundColor: warnaKelompok[w] + 'd0', borderColor: W.permukaan, borderWidth: 2, hoverBorderColor: W.tinta,
                data: bm.titik.filter((t) => t.wilayah === w).map((t) => ({ x: t.karhutla, y: t.deforestasi, r: 5 + 22 * Math.sqrt(t.kayu / maks), nama: t.nama, kayu: t.kayu })),
            })),
        },
        options: {
            layout: { padding: { right: 20 } },
            scales: {
                x: { ...sumbuRingkas(), min: 0, grace: '8%', title: { display: true, text: 'Total karhutla 2018–2022 (ha)', color: W.tinta3 } },
                y: { ...sumbuRingkas(), grace: '12%', title: { display: true, text: 'Total deforestasi netto 2018–2022 (ha)', color: W.tinta3 } },
            },
            plugins: {
                tooltip: { callbacks: { title: (it) => it[0].raw.nama, label: (c) => [` Karhutla: ${ringkas(c.raw.x)} ha`, ` Deforestasi: ${ringkas(c.raw.y)} ha`, ` Kayu: ${ringkas(c.raw.kayu)} m³`] } },
            },
        },
        plugins: [{
            id: 'namaTitik',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = `500 11px ${Chart.defaults.font.family}`;
                ctx.fillStyle = W.tinta2;
                ctx.textBaseline = 'middle';
                const dipakai = [];
                chart.data.datasets.forEach((ds, i) => chart.getDatasetMeta(i).data.forEach((el, j) => {
                    const p = ds.data[j];
                    if (!(p.x > 200000 || p.y > 50000)) return;
                    const x = el.x + el.options.radius + 5;
                    const w = ctx.measureText(p.nama).width;
                    let y = el.y;
                    while (dipakai.some((b) => x < b.x + b.w && x + w > b.x && Math.abs(y - b.y) < 13)) y += 13;
                    dipakai.push({ x, y, w });
                    ctx.fillText(p.nama, x, y);
                }));
                ctx.restore();
            },
        }],
    };
};

// ---- detail provinsi di peta sebaran
DEF.chartDetailProvinsi = () => {
    const ds = D.sebaran.dataset[peta.data];
    const label = Object.keys(ds.nilai);
    const nilai = label.map((t) => ds.nilai[t][peta.kode] ?? null);
    const warna = warnaKuat();
    return {
        type: 'bar',
        data: { labels: label, datasets: [{ label: ds.satuan, data: nilai, backgroundColor: label.map((t) => (t === peta.tahun ? warna : W.redup)), ...gayaBatang, borderSkipped: false }] },
        options: {
            scales: { y: { ...sumbuRingkas(), grace: '10%' }, x: { ...tanpaKisi, ticks: { font: { size: 10 }, maxRotation: 45, autoSkip: false, callback(v) { return labelSumbu(this.getLabelForValue(v)); } } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(ds.satuan) } } },
        },
    };
};

// ================================================================== pembuatan grafik secara lazy
const grafik = {};
const terlihat = (el) => el && el.offsetParent !== null;
function pastikanGrafik(akar = document) {
    akar.querySelectorAll('canvas[id]').forEach((cv) => {
        if (grafik[cv.id] || !DEF[cv.id] || !terlihat(cv)) return;
        grafik[cv.id] = new Chart(cv, DEF[cv.id]());
    });
}
function bangunUlang(id) {
    if (grafik[id]) { grafik[id].destroy(); delete grafik[id]; }
    pastikanGrafik(document.getElementById(id)?.parentElement ?? document);
}
function bangunUlangSemua() {
    Object.keys(grafik).forEach((id) => { grafik[id].destroy(); delete grafik[id]; });
    pastikanGrafik();
}

// Tabel pendamping
const tabelDari = (chart) => {
    const ds = chart.data.datasets;
    const sel = (v) => `<td class="angka">${v === null || v === undefined ? '–' : fmt2.format(v)}</td>`;
    if (chart.config.type === 'bubble') {
        const baris = ds.flatMap((d) => d.data.map((p) => `<tr><td>${p.nama}</td><td>${d.label}</td>${sel(p.x)}${sel(p.y)}${sel(p.kayu)}</tr>`));
        return `<table class="tabel-data"><thead><tr><th>Provinsi</th><th>Kelompok</th><th class="angka">Karhutla (ha)</th><th class="angka">Deforestasi (ha)</th><th class="angka">Kayu (m³)</th></tr></thead><tbody>${baris.join('')}</tbody></table>`;
    }
    const kepala = ds.map((d) => `<th class="angka">${d.label || 'Nilai'}</th>`).join('');
    const baris = chart.data.labels.map((l, i) => `<tr><td>${l}</td>${ds.map((d) => sel(d.data[i])).join('')}</tr>`);
    return `<table class="tabel-data"><thead><tr><th></th>${kepala}</tr></thead><tbody>${baris.join('')}</tbody></table>`;
};
document.querySelectorAll('details.lihat-tabel[data-grafik]').forEach((det) => det.addEventListener('toggle', () => {
    if (det.open && grafik[det.dataset.grafik]) det.querySelector('.isi').innerHTML = tabelDari(grafik[det.dataset.grafik]);
}));

// ================================================================== satu halaman: grafik digambar saat masuk layar, menu ikut scroll
const pengamatGrafik = new IntersectionObserver((entri) => entri.forEach((e) => {
    if (e.isIntersecting) pastikanGrafik(e.target.parentElement);
}), { rootMargin: '0px 0px -10% 0px' });
document.querySelectorAll('canvas[id]').forEach((cv) => DEF[cv.id] && pengamatGrafik.observe(cv));

const tautanMenu = [...document.querySelectorAll('[data-tab]')];
const pengamatMenu = new IntersectionObserver((entri) => entri.forEach((e) => {
    if (e.isIntersecting) tautanMenu.forEach((a) => a.setAttribute('aria-current', String(a.dataset.tab === e.target.id)));
}), { rootMargin: '-40% 0px -55% 0px' });
document.querySelectorAll('[data-panel]').forEach((s) => pengamatMenu.observe(s));

const akar = document.documentElement;

// ================================================================== per data: panggung scroll (layar menempel, adegan & latar mengikuti scroll)
// Tiap .trek berisi panggung sticky. --m (0 -> 1) = panggung masuk dari bawah, --p (0 -> 1) = lintasan menempel; latar halaman dicampur antar-data di akhir tiap lintasan.
const LATAR = {
    terang: ['#cfe8d6', '#dcedb4', '#3c0008', '#d9e0ae', '#c8e3d5'],
    gelap: ['#0f2a1b', '#15301a', '#3c0008', '#222915', '#0f2a26'],
};
const LATAR_AWAL = { terang: '#f2eee3', gelap: '#0e1a13' };
const trek = [...document.querySelectorAll('.trek')];
const gerakPenuh = () => !matchMedia('(max-width: 1023px), (max-height: 680px), (prefers-reduced-motion: reduce)').matches;
let menunggu = false;
function perbaruiTrek() {
    menunggu = false;
    if (!trek.length) return;
    if (!gerakPenuh()) { akar.style.removeProperty('--page'); delete akar.dataset.latar; trek.forEach((t) => { t.style.removeProperty('--p'); t.style.removeProperty('--m'); }); return; }
    const tinggi = window.innerHeight;
    const palet = LATAR[akar.dataset.tema === 'terang' ? 'terang' : 'gelap'];
    const awal = LATAR_AWAL[akar.dataset.tema === 'terang' ? 'terang' : 'gelap'];
    let warna = null;
    let indeksLatar = -1; // palet yang sedang dominan (untuk menandai latar maroon)
    trek.forEach((t, i) => {
        const r = t.getBoundingClientRect();
        const p = Math.min(1, Math.max(0, -r.top / Math.max(1, r.height - tinggi)));
        t.style.setProperty('--p', p.toFixed(4));
        t.style.setProperty('--m', Math.min(1, Math.max(0, (tinggi - r.top) / (tinggi * 0.6))).toFixed(3));
        if (r.top > tinggi || r.bottom < 0) return;
        if (r.top <= 0 && r.bottom > 0) {
            const terakhir = i + 1 >= palet.length;
            const w = Math.min(1, Math.max(0, (p - 0.55) / 0.45));
            if (!terakhir) {
                // setelah lintasan selesai (p = 1) latar tetap warna data berikutnya sampai panggung berikutnya menempel
                warna = w > 0 ? `color-mix(in srgb, ${palet[i + 1]} ${(w * 100).toFixed(1)}%, ${palet[i]})` : palet[i];
                indeksLatar = w > 0.5 ? i + 1 : i;
            } else {
                // data terakhir: saat panggung terlepas, latar kembali ke warna halaman biasa
                const kembali = Math.min(1, Math.max(0, 1 - r.bottom / tinggi));
                warna = kembali > 0 ? `color-mix(in srgb, ${awal} ${(kembali * 100).toFixed(1)}%, ${palet[i]})` : palet[i];
                indeksLatar = kembali > 0.5 ? -1 : i;
            }
        } else if (i === 0 && r.top > 0) {
            const w = Math.min(1, Math.max(0, 1 - r.top / tinggi));
            warna = `color-mix(in srgb, ${palet[0]} ${(w * 100).toFixed(1)}%, ${awal})`;
        }
    });
    if (warna) akar.style.setProperty('--page', warna); else akar.style.removeProperty('--page');
    if (indeksLatar === 2) akar.dataset.latar = 'gelap'; else delete akar.dataset.latar;
}
const jadwalkan = () => { if (!menunggu) { menunggu = true; requestAnimationFrame(perbaruiTrek); } };
window.addEventListener('scroll', jadwalkan, { passive: true });
window.addEventListener('resize', jadwalkan);
new MutationObserver(jadwalkan).observe(akar, { attributes: true, attributeFilter: ['data-tema'] });
perbaruiTrek();

// Scroll snapping D1 -> D2 -> ... : setelah scroll berhenti, layar meluncur ke titik berikutnya (panel terlihat penuh) menurut arah scroll.
// Hanya aktif di antara titik D1 dan D5; di luar itu scroll bebas. Berlaku untuk roda mouse, touchpad, keyboard, dan seret scrollbar.
const jangkar = trek.map((t) => t.querySelector('.jangkar')).filter(Boolean);
let awalScroll = window.scrollY;
let targetOtomatis = null;
let timerBerhenti = null;
function titikHenti() { return jangkar.map((j) => Math.round(j.getBoundingClientRect().top + window.scrollY)); }
function setelahBerhenti() {
    timerBerhenti = null;
    const y = window.scrollY;
    const titik = titikHenti();
    let mulai = awalScroll;
    if (targetOtomatis !== null) { mulai = targetOtomatis; targetOtomatis = null; if (Math.abs(y - mulai) < 4) { awalScroll = y; return; } }
    awalScroll = y;
    if (!gerakPenuh() || titik.length < 2 || y < titik[0] - 3 || y > titik[titik.length - 1] + 3) return;
    if (titik.some((t) => Math.abs(t - y) < 4)) return;
    const arah = Math.sign(y - mulai);
    if (!arah) return;
    const kandidat = arah > 0 ? titik.filter((t) => t > mulai + 3) : titik.filter((t) => t < mulai - 3);
    if (!kandidat.length) return;
    const tujuan = kandidat.reduce((a, b) => (Math.abs(b - y) < Math.abs(a - y) ? b : a));
    targetOtomatis = tujuan;
    awalScroll = tujuan;
    window.scrollTo({ top: tujuan, behavior: 'smooth' });
}
window.addEventListener('scroll', () => {
    clearTimeout(timerBerhenti);
    timerBerhenti = setTimeout(setelahBerhenti, 140);
}, { passive: true });

// ================================================================== per data: pilihan tahun peringkat provinsi
[['pilihTahunKarhutla', 'chartKarhutlaProvinsi'], ['pilihTahunKayu', 'chartKayuProvinsi']].forEach(([sel, id]) =>
    document.getElementById(sel).addEventListener('change', () => bangunUlang(id)));

// ================================================================== peta sebaran (tile map)
const peta = { data: null, tahun: null, kode: null };
const rampAktif = () => W.ramp[RAMP_PETA[peta.data]] ?? W.ramp.hijau;
const warnaKuat = () => (peta.data === 'D5' ? W.turun : rampAktif()[3]);
const elPeta = document.getElementById('peta');
const elData = document.getElementById('sebaranData');
const elTahun = document.getElementById('sebaranTahun');
const tombolPilihPeta = [...document.querySelectorAll('[data-pilih-peta]')];
const satuanTeks = (v, satuan) => (satuan === '%' ? `${fmt.format(v)}%` : `${ringkas(v)} ${satuan}`);

const kuantil = (arr, q) => {
    const s = [...arr].sort((a, b) => a - b);
    const pos = (s.length - 1) * q;
    const lo = Math.floor(pos);
    return s[lo] + (s[Math.min(lo + 1, s.length - 1)] - s[lo]) * (pos - lo);
};

function skalaWarna(ds, nilaiTahun) {
    const vals = Object.values(nilaiTahun).filter((v) => v !== null && v !== undefined);
    if (ds.skala === 'divergen') {
        const pos = vals.filter((v) => v > 0);
        const neg = vals.filter((v) => v < 0).map(Math.abs);
        const batasPos = [0.25, 0.5, 0.75].map((q) => kuantil(pos, q));
        const batasNeg = neg.length ? kuantil(neg, 0.5) : 0;
        const warna = (v) => {
            if (v === 0) return W.nol;
            if (v < 0) return Math.abs(v) <= batasNeg ? W.reforestasi[0] : W.reforestasi[1];
            const i = batasPos.findIndex((b) => v <= b);
            return W.deforestasi[i === -1 ? 3 : i];
        };
        const legenda = [
            ...(neg.length ? [[W.reforestasi[1], `≤ −${ringkas(batasNeg)}`], [W.reforestasi[0], 'reforestasi']] : []),
            [W.deforestasi[0], `≤ ${ringkas(batasPos[0])}`], [W.deforestasi[1], `≤ ${ringkas(batasPos[1])}`],
            [W.deforestasi[2], `≤ ${ringkas(batasPos[2])}`], [W.deforestasi[3], `> ${ringkas(batasPos[2])}`],
        ];
        return { warna, legenda };
    }
    const positif = vals.filter((v) => v > 0);
    const batas = [0.2, 0.4, 0.6, 0.8].map((q) => kuantil(positif, q));
    const fmtBatas = (v) => (ds.satuan === '%' ? `${fmt.format(v)}%` : ringkas(v));
    const warna = (v) => {
        if (v <= 0) return W.nol;
        const i = batas.findIndex((b) => v <= b);
        return rampAktif()[i === -1 ? 4 : i];
    };
    const legenda = [
        ...(vals.some((v) => v <= 0) ? [[W.nol, '0']] : []),
        [rampAktif()[0], `≤ ${fmtBatas(batas[0])}`], [rampAktif()[1], `≤ ${fmtBatas(batas[1])}`], [rampAktif()[2], `≤ ${fmtBatas(batas[2])}`],
        [rampAktif()[3], `≤ ${fmtBatas(batas[3])}`], [rampAktif()[4], `> ${fmtBatas(batas[3])}`],
    ];
    return { warna, legenda };
}

let geo = null;
fetch('/peta/provinsi.json').then((r) => r.json()).then((g) => { geo = g; gambarPeta(); });

const tooltipPeta = Object.assign(document.createElement('div'), {
    className: 'pointer-events-none fixed z-50 hidden rounded-xl border border-white/10 px-3 py-2 text-xs text-white shadow-xl',
});
document.body.append(tooltipPeta);

function isiTahun() {
    const ds = D.sebaran.dataset[peta.data];
    const daftar = Object.keys(ds.nilai);
    elTahun.innerHTML = daftar.map((t) => `<option value="${t}">${t}</option>`).join('');
    // bawaan: tahun penuh terakhir (label berkurung seperti '2026 (Jan–Jul)' = tahun belum lengkap)
    peta.tahun = daftar.includes(peta.tahun) ? peta.tahun : daftar.filter((t) => !t.includes('(')).pop();
    elTahun.value = peta.tahun;
}

function terapkanTemaPeta(kode) {
    ['wadahSebaran', 'kartuPetaUtama', 'kartuDetailProvinsi', 'kartuTop5Provinsi'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.dataset.ds = kode;
    });
    ['lencanaPetaKode', 'lencanaDetailKode', 'lencanaTop5Kode'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.textContent = kode;
    });
    tombolPilihPeta.forEach((btn) => btn.setAttribute('aria-pressed', String(btn.dataset.pilihPeta === kode)));
}

function gambarPeta() {
    if (!peta.data) { peta.data = elData.value; isiTahun(); }
    terapkanTemaPeta(peta.data);
    const ds = D.sebaran.dataset[peta.data];
    const nilaiTahun = ds.nilai[peta.tahun] ?? {};
    const { warna, legenda } = skalaWarna(ds, nilaiTahun);
    const urut = Object.entries(nilaiTahun).filter(([, v]) => v !== null).sort((a, b) => b[1] - a[1]);
    const peringkat = Object.fromEntries(urut.map(([k], i) => [k, i + 1]));

    document.getElementById('sebaranJudul').textContent = `${ds.label}, ${peta.tahun}`;
    document.getElementById('sebaranCatatan').textContent = ds.catatan;
    document.getElementById('sebaranLegenda').innerHTML = legenda
        .map(([w, l]) => `<span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-[4px] border border-garis" style="background:${w}"></span>${l}</span>`)
        .join('');

    // provinsi terpilih: bawaan = peringkat 1
    if (!peta.kode || !D.sebaran.provinsi.some((p) => p.kode === peta.kode)) peta.kode = urut[0]?.[0];

    // Peta provinsi (SVG). Batas wilayah dimuat sekali dari /peta/provinsi.json (hasil etl/siapkan_peta.py).
    if (geo) {
        const provinsi = Object.fromEntries(D.sebaran.provinsi.map((p) => [p.kode, p]));
        elPeta.innerHTML = `<svg viewBox="${geo.viewBox}" class="h-auto w-full"><defs><pattern id="arsir" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <rect width="5" height="5" fill="${W.nol}"/><line x1="0" y1="0" x2="0" y2="5" stroke="${W.sumbu}" stroke-width="1.5"/></pattern></defs>${
            geo.provinsi.map((g) => `<path data-kode="${g.kode}" d="${g.d}"/>`).join('')}</svg>`;
        elPeta.querySelectorAll('path').forEach((el) => {
            const p = provinsi[el.dataset.kode];
            const v = nilaiTahun[p.kode];
            const kosong = v === undefined || v === null;
            const digabung = kosong && ds.kaltaraDigabung && p.kode === '65';
            const dipilih = p.kode === peta.kode;
            Object.assign(el.style, { fill: digabung ? 'url(#arsir)' : kosong ? W.nol : warna(v), stroke: dipilih ? W.tinta : W.permukaan,
                strokeWidth: dipilih ? '1.6' : '0.6', strokeLinejoin: 'round', cursor: 'pointer', outline: 'none' });
            el.setAttribute('tabindex', '0');
            el.setAttribute('role', 'button');
            el.setAttribute('aria-label', `${p.nama}: ${kosong ? 'tidak ada data' : satuanTeks(v, ds.satuan)}`);
            const tampilkanInfo = (x, y) => {
                tooltipPeta.style.background = W.tooltip;
                tooltipPeta.innerHTML = `<div class="font-semibold">${p.nama}</div><div class="mt-0.5 opacity-80">${digabung ? 'Data digabung ke Kalimantan Timur'
                    : kosong ? 'Tidak ada data' : `${satuanTeks(v, ds.satuan)} · peringkat ${peringkat[p.kode]} dari ${urut.length}`}</div>`;
                Object.assign(tooltipPeta.style, { left: `${x + 14}px`, top: `${y + 14}px` });
                tooltipPeta.classList.remove('hidden');
            };
            el.addEventListener('mousemove', (e) => tampilkanInfo(e.clientX, e.clientY));
            el.addEventListener('mouseleave', () => tooltipPeta.classList.add('hidden'));
            el.addEventListener('click', () => { peta.kode = p.kode; gambarPeta(); });
            el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); peta.kode = p.kode; gambarPeta(); } });
            if (dipilih) el.parentNode.append(el); // garis tepi provinsi terpilih tampil di atas tetangganya
        });
    }
    const prov = D.sebaran.provinsi.find((p) => p.kode === peta.kode);
    const v = nilaiTahun[peta.kode];
    document.getElementById('detailNama').textContent = prov?.nama ?? '—';
    document.getElementById('detailWilayah').textContent = prov ? `Wilayah ${prov.wilayah}` : '';
    document.getElementById('detailNilai').textContent = v === undefined || v === null ? '–' : (ds.satuan === '%' ? fmt.format(v) : ringkas(v));
    document.getElementById('detailSatuan').textContent = v === undefined || v === null ? '' : ds.satuan === '%' ? '% daratan' : ds.satuan;
    document.getElementById('detailPeringkat').textContent = peringkat[peta.kode] ? `Peringkat ${peringkat[peta.kode]} dari ${urut.length} provinsi pada ${peta.tahun}` : 'Tidak ada data pada periode ini';

    const maks = urut[0]?.[1] || 1;
    document.getElementById('top5Ket').textContent = `${ds.label}, ${peta.tahun}`;
    document.getElementById('top5').innerHTML = urut.slice(0, 5).map(([k, nilai], i) => {
        const nama = D.sebaran.provinsi.find((p) => p.kode === k)?.nama ?? k;
        return `<li class="text-[12.5px]"><div class="flex justify-between gap-2"><span class="text-ink"><span class="mr-1.5 text-ink-3">${i + 1}</span>${nama}</span>
            <span class="font-semibold tabular-nums text-ink-2">${satuanTeks(nilai, ds.satuan)}</span></div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-2"><div class="h-full rounded-full" style="width:${Math.max(2, (nilai / maks) * 100)}%;background:${warnaKuat()}"></div></div></li>`;
    }).join('');

    bangunUlang('chartDetailProvinsi');
    const det = document.querySelector('[data-tabel-peta]');
    if (det.open) isiTabelPeta();
}

function isiTabelPeta() {
    const ds = D.sebaran.dataset[peta.data];
    const nilai = ds.nilai[peta.tahun] ?? {};
    const baris = D.sebaran.provinsi.map((p) => [p.nama, nilai[p.kode]]).sort((a, b) => (b[1] ?? -Infinity) - (a[1] ?? -Infinity));
    document.querySelector('[data-tabel-peta] .isi').innerHTML = `<table class="tabel-data"><thead><tr><th>Provinsi</th><th class="angka">${ds.label} (${ds.satuan})</th></tr></thead><tbody>${
        baris.map(([n, v]) => `<tr><td>${n}</td><td class="angka">${v === undefined || v === null ? '–' : fmt2.format(v)}</td></tr>`).join('')}</tbody></table>`;
}
document.querySelector('[data-tabel-peta]').addEventListener('toggle', (e) => { if (e.target.open) isiTabelPeta(); });
elData.addEventListener('change', () => { peta.data = elData.value; peta.kode = null; isiTahun(); gambarPeta(); });
elTahun.addEventListener('change', () => { peta.tahun = elTahun.value; gambarPeta(); });
tombolPilihPeta.forEach((btn) => btn.addEventListener('click', () => {
    if (elData.value === btn.dataset.pilihPeta) return;
    elData.value = btn.dataset.pilihPeta;
    elData.dispatchEvent(new Event('change'));
}));

// ================================================================== tema terang/gelap
document.getElementById('gantiTema').addEventListener('click', () => {
    html.dataset.tema = html.dataset.tema === 'terang' ? 'gelap' : 'terang';
    try { localStorage.setItem('tema', html.dataset.tema); } catch (e) { /* penyimpanan tidak tersedia */ }
    terapkanGayaDasar();
    bangunUlangSemua();
    gambarPeta();
});

// ================================================================== mulai
terapkanGayaDasar();
gambarPeta();
