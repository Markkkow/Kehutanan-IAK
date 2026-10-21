{{--
    Latar layar penuh untuk pembuka tiap bab (D1–D5), digambar dari SVG + CSS (tanpa foto).
    Tiap lapisan punya data-lapis = kecepatan parallax (0 = diam, makin besar makin cepat bergerak saat digulir).
    Bentuk dibangkitkan dengan bilangan acak semu yang tetap, jadi gambarnya sama setiap kali dimuat.
--}}
@php
    $W = 1600; $H = 600;
    $acakDari = function (int $benih) { return function () use (&$benih) { $benih = ($benih * 1103515245 + 12345) % 2147483648; return $benih / 2147483648; }; };

    // Pinus bergerigi (tanpa batang kotak supaya tidak menyerupai rumah)
    $pinus = function (int $jumlah, int $benih, float $tMin, float $tMaks, float $dasar) use ($W, $acakDari) {
        $acak = $acakDari($benih); $d = '';
        for ($i = 0; $i < $jumlah; $i++) {
            $x = ($i + $acak() * 0.8) * ($W / $jumlah);
            $t = $tMin + $acak() * ($tMaks - $tMin);
            $l = $t * (0.2 + $acak() * 0.06);
            $tingkat = 6;
            $kiri = []; $kanan = [];
            for ($j = 0; $j < $tingkat; $j++) {
                $y = $dasar - $t * ($j / $tingkat) * 0.92;
                $w = $l * (1 - $j / $tingkat);
                $kiri[] = sprintf('%.1f %.1f', $x - $w, $y);
                $kiri[] = sprintf('%.1f %.1f', $x - $w * 0.45, $y - $t * 0.09);
                $kanan[] = sprintf('%.1f %.1f', $x + $w * 0.45, $y - $t * 0.09);
                $kanan[] = sprintf('%.1f %.1f', $x + $w, $y);
            }
            $d .= 'M'.implode('L', $kiri).sprintf('L%.1f %.1f', $x, $dasar - $t).'L'.implode('L', array_reverse($kanan)).'Z';
        }
        return $d.sprintf('M0 %.1fH%dV%dH0Z', $dasar - 4, $W, 600);
    };

    // Tajuk hutan hujan: deretan lingkaran bertumpuk (pohon berdaun lebar) di atas garis dasar
    $lingkaran = fn ($x, $y, $r) => sprintf('M%.1f %.1fa%.1f %.1f 0 1 0 %.1f 0a%.1f %.1f 0 1 0 %.1f 0Z', $x - $r, $y, $r, $r, $r * 2, $r, $r, -$r * 2);
    $tajuk = function ($x, $y, $r, $acak) use ($lingkaran) {
        $d = $lingkaran($x, $y, $r * 0.62);
        foreach ([[-0.55, 0.15], [0.55, 0.12], [-0.25, -0.38], [0.3, -0.34], [0, 0.32]] as [$dx, $dy]) {
            $d .= $lingkaran($x + $dx * $r, $y + $dy * $r, $r * (0.38 + $acak() * 0.18));
        }
        return $d;
    };
    $kanopi = function (int $jumlah, int $benih, float $rMin, float $rMaks, float $dasar) use ($W, $acakDari, $tajuk) {
        $acak = $acakDari($benih); $d = '';
        for ($i = 0; $i < $jumlah; $i++) {
            $x = ($i + $acak()) * ($W / $jumlah);
            $r = $rMin + $acak() * ($rMaks - $rMin);
            $d .= $tajuk($x, $dasar - $r * (0.3 + $acak() * 0.7), $r, $acak);
        }
        return $d.sprintf('M0 %.1fH%dV%dH0Z', $dasar - 10, $W, 600);
    };
    // Pohon berdaun lebar utuh untuk baris terdepan: batang meruncing + cabang + tajuk bergerigi
    $pohonDaun = function (int $jumlah, int $benih, float $tMin, float $tMaks, float $dasar) use ($W, $acakDari, $tajuk) {
        $acak = $acakDari($benih); $d = '';
        for ($i = 0; $i < $jumlah; $i++) {
            $x = ($i + 0.2 + $acak() * 0.6) * ($W / $jumlah);
            $t = $tMin + $acak() * ($tMaks - $tMin); $b = $t * 0.035;
            $atas = $dasar - $t * 0.62;
            $d .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fZ', $x - $b * 1.8, $dasar, $x - $b * 0.5, $atas, $x + $b * 0.5, $atas, $x + $b * 1.8, $dasar);
            $d .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fZ', $x - $b * 0.4, $atas + $t * 0.12, $x - $t * 0.16, $atas - $t * 0.05, $x - $b * 0.2, $atas + $t * 0.05);
            $d .= $tajuk($x, $dasar - $t * 0.78, $t * 0.3, $acak);
        }
        return $d.sprintf('M0 %.1fH%dV%dH0Z', $dasar - 4, $W, 600);
    };

    // Bukit bergelombang
    $bukit = function (int $benih, float $dasar, float $tinggi) use ($W, $acakDari) {
        $acak = $acakDari($benih); $titik = 7; $d = sprintf('M0 %.1f', $dasar - $acak() * $tinggi);
        for ($i = 1; $i <= $titik; $i++) {
            $x = $i * $W / $titik; $y = $dasar - $acak() * $tinggi;
            $d .= sprintf('S%.1f %.1f %.1f %.1f', $x - $W / $titik / 2, $y, $x, $y);
        }
        return $d.sprintf('V600H0Z');
    };

    // Pinus kecil di atas bukit, hanya pada potongan tertentu (bagian lain gundul)
    $hutanBerpetak = function (int $benih, float $dasar, array $petak) use ($pinus, $acakDari) {
        $acak = $acakDari($benih); $d = '';
        foreach ($petak as [$dari, $sampai]) {
            for ($x = $dari; $x < $sampai; $x += 14 + $acak() * 10) {
                $t = 34 + $acak() * 30; $l = $t * 0.24; $y = $dasar - $acak() * 12;
                $d .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fZ', $x - $l, $y, $x, $y - $t, $x + $l, $y);
            }
        }
        return $d;
    };

    // Tunggul pohon dengan puncak tak rata + akar, dan batang tumbang
    $tunggul = function (int $jumlah, int $benih, float $dasar, float $skala) use ($W, $acakDari) {
        $acak = $acakDari($benih); $d = '';
        for ($i = 0; $i < $jumlah; $i++) {
            $x = ($i + $acak() * 0.7) * ($W / $jumlah); $y = $dasar + $acak() * 60 * $skala;
            $w = (10 + $acak() * 14) * $skala; $h = (14 + $acak() * 22) * $skala;
            $d .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fZ',
                $x - $w * 1.5, $y, $x - $w, $y - $h * 0.3, $x - $w * 0.9, $y - $h, $x - $w * 0.2, $y - $h * 1.12,
                $x + $w * 0.4, $y - $h * 0.92, $x + $w * 0.95, $y - $h * 1.05, $x + $w, $y - $h * 0.3, $x + $w * 1.6, $y);
            if ($acak() < 0.35) { // batang tumbang di sebelahnya
                $p = (60 + $acak() * 90) * $skala; $t = (7 + $acak() * 5) * $skala;
                $d .= sprintf('M%.1f %.1fh%.1fa%.1f %.1f 0 0 1 0 %.1fh%.1fa%.1f %.1f 0 0 1 0 %.1fZ',
                    $x + $w * 2, $y - $t * 2, $p, $t, $t, $t * 2, -$p, $t, $t, -$t * 2);
            }
        }
        return $d;
    };

    // Tumpukan kayu gelondong: penampang lingkaran + lingkar tahun
    $gelondong = function (float $kiri, float $dasar, float $r) {
        $lingkar = []; $baris = [[0, 6], [1, 5], [2, 4]];
        foreach ($baris as [$b, $n]) {
            for ($i = 0; $i < $n; $i++) {
                $lingkar[] = [$kiri + $r + $i * $r * 2.02 + $b * $r, $dasar - $r - $b * $r * 1.74, $r];
            }
        }
        return $lingkar;
    };
@endphp

<div class="latar" data-latar="{{ $kode }}" aria-hidden="true">
    <div class="latar-langit"></div>

    @switch($kode)
        @case('D1')
            <svg class="latar-lapis" data-lapis="0.15" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice"><path fill="#1f5a3c" opacity="0.55" d="{{ $kanopi(34, 3, 34, 64, 380) }}" /></svg>
            <div class="latar-kabut"><i></i><i></i></div>
            <svg class="latar-lapis" data-lapis="0.3" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice"><path fill="#123d28" d="{{ $kanopi(28, 11, 40, 72, 500) }}" /></svg>
            <svg class="latar-lapis" data-lapis="0.55" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice"><path fill="#06180f" d="{{ $pohonDaun(10, 29, 200, 290, 600) }}" /></svg>
            @break

        @case('D2')
            <svg class="latar-lapis" data-lapis="0.12" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#2f5a3a" opacity="0.7" d="{{ $bukit(5, 380, 120) }}" />
                <path fill="#244a30" opacity="0.8" d="{{ $hutanBerpetak(8, 330, [[0, 380], [620, 900], [1180, 1600]]) }}" />
            </svg>
            <svg class="latar-lapis" data-lapis="0.3" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#4a5a32" d="{{ $bukit(17, 470, 110) }}" />
                <path fill="#183d26" d="{{ $hutanBerpetak(21, 420, [[0, 260], [430, 760], [1050, 1250]]) }}" />
            </svg>
            <svg class="latar-lapis" data-lapis="0.5" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#0c2416" d="{{ $bukit(33, 560, 90) }}" />
                <path fill="#071a0f" d="{{ $hutanBerpetak(41, 520, [[150, 520], [900, 1450]]) }}" />
            </svg>
            @break

        @case('D3')
            <div class="latar-asap"><i></i><i></i><i></i></div>
            <svg class="latar-lapis latar-api-jauh" data-lapis="0.15" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice"><path fill="#2a0f08" d="{{ $pinus(46, 7, 120, 220, 470) }}" /></svg>
            <div class="latar-nyala"></div>
            <svg class="latar-lidah" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                @foreach ([[120, 0.9], [360, 1.2], [610, 0.8], [880, 1.3], [1130, 1], [1390, 1.15]] as $n => [$x, $s])
                    <path class="lidah" style="--n: {{ $n }}" d="M{{ $x - 40 * $s }} 560C{{ $x - 40 * $s }} {{ 470 - 40 * $s }} {{ $x - 10 }} {{ 470 - 70 * $s }} {{ $x }} {{ 390 - 90 * $s }}C{{ $x + 14 }} {{ 470 - 70 * $s }} {{ $x + 40 * $s }} {{ 470 - 40 * $s }} {{ $x + 40 * $s }} 560Z" />
                @endforeach
            </svg>
            <svg class="latar-lapis" data-lapis="0.45" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice"><path fill="#050302" d="{{ $pinus(28, 42, 150, 250, 600) }}" /></svg>
            <canvas class="latar-bara" data-bara></canvas>
            @break

        @case('D4')
            <svg class="latar-lapis" data-lapis="0.15" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                {{-- barisan hutan tanaman: pohon seragam, berjarak sama --}}
                @for ($i = 0; $i < 40; $i++)
                    <path fill="#3a2614" opacity="0.75" d="M{{ 20 + $i * 40 }} 420l-14 0l14 -90l14 90Z" />
                @endfor
                <path fill="#3a2614" opacity="0.75" d="M0 416H1600V600H0Z" />
            </svg>
            <svg class="latar-lapis" data-lapis="0.5" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#1c1209" d="M0 560H1600V600H0Z" />
                @foreach ($gelondong(860, 562, 46) as [$cx, $cy, $r])
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="#7a4a22" stroke="#1c1209" stroke-width="5" />
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.72 }}" fill="none" stroke="#5c3518" stroke-width="2" />
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.45 }}" fill="none" stroke="#5c3518" stroke-width="2" />
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.18 }}" fill="#4a2a12" />
                @endforeach
            </svg>
            @break

        @case('D5')
            <svg class="latar-lapis" data-lapis="0.12" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                {{-- tepi hutan yang tersisa di kejauhan --}}
                <path fill="#3d3415" opacity="0.85" d="{{ $kanopi(40, 13, 22, 40, 385) }}" />
            </svg>
            <div class="latar-debu"><i></i><i></i></div>
            <svg class="latar-lapis" data-lapis="0.3" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#5a4318" d="M0 400C300 385 520 410 800 398S1300 380 1600 395V600H0Z" />
                <path fill="#2c200b" d="{{ $tunggul(14, 9, 425, 1.3) }}" />
            </svg>
            <svg class="latar-lapis" data-lapis="0.5" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
                <path fill="#241a08" d="M0 520C380 505 700 530 1000 512S1400 500 1600 515V600H0Z" />
                <path fill="#120c03" d="{{ $tunggul(7, 23, 535, 3.2) }}" />
            </svg>
            @break
    @endswitch
</div>
