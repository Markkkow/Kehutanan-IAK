{{--
    Latar bab D3: hutan yang terbakar. Dibuat dari SVG + CSS + kanvas bara (tanpa foto).
    Lapisan dari belakang ke depan: langit membara, asap, pepohonan jauh, cahaya api, pepohonan dekat, bara.
    Siluet pinus dibangkitkan dengan bilangan acak semu yang tetap (hasil sama setiap kali dimuat).
--}}
@php
    $pinus = function (int $jumlah, int $benih, float $tinggiMin, float $tinggiMaks) {
        $acak = function () use (&$benih) { $benih = ($benih * 1103515245 + 12345) % 2147483648; return $benih / 2147483648; };
        $jalur = '';
        for ($i = 0; $i < $jumlah; $i++) {
            $x = ($i + $acak() * 0.6) * (1000 / $jumlah);
            $t = $tinggiMin + $acak() * ($tinggiMaks - $tinggiMin);
            $l = $t * (0.28 + $acak() * 0.08);
            $dasar = 300;
            // pinus bertingkat: tiga segitiga yang makin kecil ke atas
            foreach ([[0, 1], [0.32, 0.78], [0.6, 0.55]] as [$naik, $lebar]) {
                $y0 = $dasar - $t * $naik;
                $jalur .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fZ', $x - $l * $lebar, $y0, $x, $y0 - $t * 0.55, $x + $l * $lebar, $y0);
            }
            $jalur .= sprintf('M%.1f %dL%.1f %dL%.1f %.1fL%.1f %.1fZ', $x - 2.5, $dasar, $x + 2.5, $dasar, $x + 2.5, $dasar - $t * 0.15, $x - 2.5, $dasar - $t * 0.15);
        }
        return $jalur;
    };
@endphp
<div class="latar-api" aria-hidden="true" data-latar-api>
    <div class="latar-api-langit"></div>
    <div class="latar-api-asap"><i></i><i></i><i></i></div>
    <svg class="latar-api-pohon latar-api-jauh" viewBox="0 0 1000 300" preserveAspectRatio="xMidYMax slice">
        <path d="{{ $pinus(34, 7, 90, 170) }}M0 300H1000V280H0Z" />
    </svg>
    <div class="latar-api-nyala"></div>
    <svg class="latar-api-pohon latar-api-dekat" viewBox="0 0 1000 300" preserveAspectRatio="xMidYMax slice">
        <path d="{{ $pinus(22, 42, 150, 250) }}M0 300H1000V262H0Z" />
    </svg>
    <canvas class="latar-api-bara" data-bara></canvas>
</div>
