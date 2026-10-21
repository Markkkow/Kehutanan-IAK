<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D4 - Produksi kayu bulat per provinsi per tahun (m3). Sumber: BPS (JSON). */
class ProduksiKayu extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.fakta_produksi_kayu';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
