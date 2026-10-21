<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo (lihat config/akun_demo.php). Password otomatis di-hash (bcrypt) oleh cast 'hashed' di model User.
     */
    public function run(): void
    {
        foreach (config('akun_demo') as $akun) {
            User::updateOrCreate(
                ['email' => $akun['email']],
                ['name' => $akun['name'], 'password' => $akun['password'], 'peran' => $akun['peran']],
            );
        }
    }
}
