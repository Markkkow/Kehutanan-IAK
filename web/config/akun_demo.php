<?php

/*
 * Akun demo untuk peragaan (lokal). Dibuat oleh DatabaseSeeder dan ditampilkan di halaman login
 * dengan tombol "Masuk sebagai ...". Password di sini sengaja terbuka karena hanya untuk demo;
 * akun yang dibuat lewat halaman Kelola akun disimpan sebagai hash dan tidak ditampilkan.
 */
return [
    ['name' => 'Admin Kelompok 3', 'email' => 'admin@kehutanan.test', 'password' => 'hutan2026', 'peran' => 'admin',
        'ket' => 'Bisa mengelola akun'],
    ['name' => 'Anggota Kelompok 3', 'email' => 'anggota@kehutanan.test', 'password' => 'anggota2026', 'peran' => 'pengguna',
        'ket' => 'Hanya melihat dashboard'],
    ['name' => 'Dosen Penguji', 'email' => 'dosen@kehutanan.test', 'password' => 'dosen2026', 'peran' => 'pengguna',
        'ket' => 'Hanya melihat dashboard'],
];
