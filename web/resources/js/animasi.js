/*
 * Animasi bab per data (D1–D5) ala halaman produk: semua gerak terikat posisi scroll (scrub).
 *   Pembuka   : kata judul naik satu per satu, cahaya membesar, angka kunci menghitung dari 0,
 *               lalu layar menempel sebentar dan mengecil saat pembaca lanjut.
 *   Pembahasan: tiga kartu (Terlihat → Kenapa → Lanjut) bergeser ke samping saat digulir ke bawah.
 *   Grafik    : kartu naik muncul bertahap.
 * Layar kecil: tanpa "menempel", cukup muncul saat terlihat. prefers-reduced-motion: tanpa animasi (keadaan akhir).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const fmtBulat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

// Format sama dengan F::ringkas / F::persen di server, agar angka akhir animasi = angka di HTML
function tulisAngka(el, v) {
    const a = Math.abs(v);
    let teks;
    if (el.dataset.format === 'persen') teks = `${fmtBulat.format(a)}%`;
    else if (a >= 1e6) teks = `${fmt.format(a / 1e6)} jt`;
    else if (a >= 1e3) teks = `${fmt.format(a / 1e3)} rb`;
    else teks = fmt.format(a);
    const tanda = v < 0 ? '−' : el.dataset.tanda === '1' && v > 0 ? '+' : '';
    el.textContent = tanda + teks;
}

const daftarBab = [...document.querySelectorAll('[data-bab]')];

function animasiPembuka(bab, { tempel }) {
    const pembuka = bab.querySelector('[data-bab-pembuka]');
    const isi = pembuka.querySelector('.bab-pembuka-isi');
    const angka = pembuka.querySelector('[data-hitung]');
    const target = Number(angka.dataset.hitung);
    const hitung = { v: 0 };

    // Masuk: berjalan saat kartu pembuka naik dari bawah layar ke atas
    gsap.timeline({ scrollTrigger: { trigger: pembuka, start: 'top 85%', end: tempel ? 'top top' : 'top 25%', scrub: 0.8 } })
        .from(pembuka.querySelector('.bab-sinar'), { scale: 0.35, opacity: 0, duration: 1.4, ease: 'none' }, 0)
        .from(pembuka.querySelector('.bab-kode'), { y: 24, opacity: 0, duration: 0.5 }, 0.1)
        .from(pembuka.querySelectorAll('.kata > span'), { yPercent: 110, opacity: 0, stagger: 0.07, duration: 0.6, ease: 'power3.out' }, 0.2)
        .fromTo(hitung, { v: 0 }, { v: target, duration: 0.9, ease: 'power2.out', onUpdate: () => tulisAngka(angka, hitung.v) }, 0.55)
        .from(pembuka.querySelector('.bab-angka'), { y: 40, opacity: 0, duration: 0.5 }, 0.55)
        .from(pembuka.querySelector('.bab-ket'), { y: 24, opacity: 0, duration: 0.5 }, 0.95);

    if (!tempel) return;
    // Menempel sebentar, lalu isi mengecil & meredup saat pembahasan datang (efek "mundur" ala Apple)
    gsap.timeline({ scrollTrigger: { trigger: pembuka, start: 'top top', end: '+=70%', scrub: 0.8, pin: true, anticipatePin: 1 } })
        .to(pembuka.querySelector('.bab-sinar'), { scale: 1.25, duration: 1, ease: 'none' }, 0)
        .to(isi, { scale: 0.9, opacity: 0.25, duration: 1, ease: 'power1.in' }, 0.35)
        .to(pembuka.querySelector('.bab-gulir'), { opacity: 0, duration: 0.3 }, 0);
}

function animasiPembahasan(bab) {
    const slide = bab.querySelector('[data-bab-slide]');
    const jalur = slide.querySelector('.bab-slide-jalur');
    const kartu = slide.querySelectorAll('.bab-kartu');
    slide.classList.add('bab-slide-gsap');
    const jarak = () => Math.max(0, jalur.scrollWidth - slide.clientWidth);

    const geser = gsap.to(jalur, {
        x: () => -jarak(),
        ease: 'none',
        scrollTrigger: { trigger: slide, start: 'center center', end: () => `+=${jarak() + window.innerHeight * 0.25}`, scrub: 0.8, pin: true, anticipatePin: 1, invalidateOnRefresh: true },
    });
    // Tiap kartu menyala saat bergeser masuk ke tengah
    kartu.forEach((k, i) => {
        if (i === 0) return;
        gsap.from(k, { opacity: 0.3, scale: 0.94, ease: 'none', scrollTrigger: { trigger: k, containerAnimation: geser, start: 'left 90%', end: 'left 45%', scrub: true } });
    });
}

function animasiGrafik(bab) {
    const kartu = bab.querySelectorAll('[data-bab-grafik] > *');
    gsap.from(kartu, {
        y: 70, opacity: 0, stagger: 0.15, duration: 0.9, ease: 'power3.out',
        scrollTrigger: { trigger: bab.querySelector('[data-bab-grafik]'), start: 'top 85%', once: true },
    });
}

// Latar layar penuh: tiap lapisan bergerak dengan kecepatan berbeda (parallax); D3 juga "menyala" saat masuk layar
function latarBab(bab, { tempel }) {
    const latar = bab.querySelector('[data-latar]');
    if (!latar) return;
    const pembuka = bab.querySelector('[data-bab-pembuka]');
    gsap.fromTo(latar, { '--nyala': 0 }, {
        '--nyala': 1, ease: 'none',
        scrollTrigger: { trigger: pembuka, start: 'top 90%', end: tempel ? 'top top' : 'top 30%', scrub: 0.8 },
    });
    latar.querySelectorAll('[data-lapis]').forEach((lapis) => {
        const k = Number(lapis.dataset.lapis);
        gsap.fromTo(lapis, { yPercent: k * 18 }, {
            yPercent: -k * 14, ease: 'none',
            scrollTrigger: { trigger: pembuka, start: 'top bottom', end: tempel ? '+=200%' : 'bottom top', scrub: true },
        });
    });
}

// Bara api: partikel kecil menyala yang naik & bergoyang. Hanya berjalan saat kanvasnya terlihat.
function baraApi(kanvas) {
    const ctx = kanvas.getContext('2d');
    const warna = ['255,190,90', '255,140,50', '255,100,40', '255,220,140'];
    let partikel = [];
    let lebar = 0;
    let tinggi = 0;
    let jalan = false;
    const ukur = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        lebar = kanvas.clientWidth;
        tinggi = kanvas.clientHeight;
        kanvas.width = lebar * dpr;
        kanvas.height = tinggi * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    const lahir = () => ({
        x: Math.random() * lebar, y: tinggi * (0.75 + Math.random() * 0.3), r: 0.8 + Math.random() * 2.2,
        vy: 0.35 + Math.random() * 1.1, ayun: Math.random() * Math.PI * 2, umur: 0, batas: 160 + Math.random() * 240,
        w: warna[Math.floor(Math.random() * warna.length)],
    });
    const gambar = () => {
        if (!jalan) return;
        ctx.clearRect(0, 0, lebar, tinggi);
        ctx.globalCompositeOperation = 'lighter';
        while (partikel.length < Math.min(90, lebar / 12)) partikel.push(lahir());
        partikel = partikel.filter((p) => p.umur < p.batas && p.y > -10);
        for (const p of partikel) {
            p.umur += 1;
            p.y -= p.vy;
            p.ayun += 0.03;
            p.x += Math.sin(p.ayun) * 0.45;
            const a = Math.sin((p.umur / p.batas) * Math.PI); // muncul lalu padam
            const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
            g.addColorStop(0, `rgba(${p.w},${0.95 * a})`);
            g.addColorStop(1, `rgba(${p.w},0)`);
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2);
            ctx.fill();
        }
        requestAnimationFrame(gambar);
    };
    ukur();
    window.addEventListener('resize', ukur);
    new IntersectionObserver(([e]) => {
        const sebelum = jalan;
        jalan = e.isIntersecting;
        if (jalan && !sebelum) requestAnimationFrame(gambar);
    }).observe(kanvas);
}

// Penanda bab di kanan: tampil saat bagian Per data terlihat; bar tiap bab terisi sesuai kemajuan baca
function penandaBab() {
    const rel = document.querySelector('[data-bab-rel]');
    if (!rel || !daftarBab.length) return;
    ScrollTrigger.create({
        trigger: daftarBab[0], endTrigger: daftarBab.at(-1), start: 'top 60%', end: 'bottom 40%',
        onToggle: (st) => rel.classList.toggle('tampil', st.isActive),
    });
    daftarBab.forEach((bab) => {
        const tautan = rel.querySelector(`[data-rel="${bab.id}"]`);
        const isian = tautan.querySelector('i');
        ScrollTrigger.create({
            trigger: bab, start: 'top 55%', end: 'bottom 55%',
            onToggle: (st) => tautan.classList.toggle('aktif', st.isActive),
            onUpdate: (st) => gsap.set(isian, { scaleY: st.progress }),
        });
    });
}

if (daftarBab.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const mm = gsap.matchMedia();
    mm.add('(min-width: 1024px)', () => {
        daftarBab.forEach((bab) => {
            animasiPembuka(bab, { tempel: true });
            latarBab(bab, { tempel: true });
            animasiPembahasan(bab);
            animasiGrafik(bab);
        });
        return () => document.querySelectorAll('.bab-slide-gsap').forEach((s) => s.classList.remove('bab-slide-gsap'));
    });
    mm.add('(max-width: 1023px)', () => {
        daftarBab.forEach((bab) => {
            animasiPembuka(bab, { tempel: false });
            latarBab(bab, { tempel: false });
            animasiGrafik(bab);
        });
    });
    penandaBab();
    document.querySelectorAll('[data-bara]').forEach(baraApi);

    // Posisi pin dihitung ulang setelah font & gambar selesai dimuat
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
    window.addEventListener('load', () => ScrollTrigger.refresh());
} else {
    penandaBab();
}
