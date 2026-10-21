<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Referensi 34 provinsi (kode BPS). */
class Provinsi extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.ref_provinsi';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'kode_bps';

    protected $keyType = 'string';
}
