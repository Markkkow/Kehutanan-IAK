<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D5 - Deforestasi netto per provinsi per periode dua tahun (ha/th). Sumber: BPS/KLHK (CSV). */
class Deforestasi extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.fakta_deforestasi';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
