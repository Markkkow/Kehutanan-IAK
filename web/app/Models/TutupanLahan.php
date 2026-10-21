<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D2 - Luas tutupan lahan hutan & non-hutan per provinsi per tahun (ha). Sumber: BPS/KLHK (CSV). */
class TutupanLahan extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.fakta_tutupan_lahan';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
