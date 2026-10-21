<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan setiap tahap ETL (extract, transform-load) beserta hasil cek kualitas. */
class EtlLog extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.etl_log';

    public $timestamps = false;

    public $incrementing = false;
}
