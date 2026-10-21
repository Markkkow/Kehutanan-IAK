<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D1 - Luas penutupan lahan nasional menurut kelas, di dalam & di luar kawasan hutan (ha). Sumber: BPS (XLS). */
class TutupanKelas extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.fakta_tutupan_kelas';

    public $timestamps = false;

    public $incrementing = false;
}
