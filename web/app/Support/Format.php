<?php

namespace App\Support;

/** Format angka gaya Indonesia untuk teks di dashboard. */
class Format
{
    public static function angka(?float $nilai, int $desimal = 0): string
    {
        return $nilai === null ? '–' : number_format($nilai, $desimal, ',', '.');
    }

    /** 125795306 -> "125,8 jt"; 529266 -> "529,3 rb" */
    public static function ringkas(?float $nilai, int $desimal = 1): string
    {
        if ($nilai === null) {
            return '–';
        }
        $abs = abs($nilai);

        return match (true) {
            $abs >= 1e9 => self::angka($nilai / 1e9, $desimal).' M',
            $abs >= 1e6 => self::angka($nilai / 1e6, $desimal).' jt',
            $abs >= 1e3 => self::angka($nilai / 1e3, $desimal).' rb',
            default => self::angka($nilai, $desimal),
        };
    }

    public static function persen(?float $nilai, int $desimal = 1, bool $tanda = false): string
    {
        if ($nilai === null) {
            return '–';
        }

        return ($tanda && $nilai > 0 ? '+' : '').self::angka($nilai, $desimal).'%';
    }
}
