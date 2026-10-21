<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D3 - Luas kebakaran hutan & lahan per provinsi per tahun (ha). Sumber: SiPongi KLHK (XLSX). */
class Karhutla extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.fakta_karhutla';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
