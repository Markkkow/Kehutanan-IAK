<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan sinkronisasi file sumber (D1-D5) -> master. */
class SyncLog extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.sync_log';

    public $timestamps = false;

    public $incrementing = false;
}
