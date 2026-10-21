<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan replikasi master -> replika (dijalankan etl/replicate.py), termasuk checksum. */
class ReplicationLog extends Model
{
    protected $connection = 'data';

    protected $table = 'kehutanan_dashboard.replication_log';

    public $timestamps = false;

    public $incrementing = false;
}
