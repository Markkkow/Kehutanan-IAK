<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supabase membuka schema `public` lewat Data API (anon key). Tabel milik Laravel (users berisi hash password)
     * diamankan dengan Row Level Security tanpa policy: Data API tidak bisa membacanya, sedangkan koneksi
     * Laravel (role pemilik tabel) tetap bisa.
     */
    private array $tabel = ['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach ($this->tabel as $t) {
            if (Schema::hasTable($t)) {
                DB::statement("ALTER TABLE \"$t\" ENABLE ROW LEVEL SECURITY");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach ($this->tabel as $t) {
            if (Schema::hasTable($t)) {
                DB::statement("ALTER TABLE \"$t\" DISABLE ROW LEVEL SECURITY");
            }
        }
    }
};
