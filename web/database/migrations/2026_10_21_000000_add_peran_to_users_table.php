<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Peran akun: admin (boleh mengelola akun) atau pengguna (hanya melihat dashboard). */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('peran', 20)->default('pengguna')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('peran');
        });
    }
};
