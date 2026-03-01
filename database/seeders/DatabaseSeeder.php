<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FishType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles & Permissions terlebih dahulu
        $this->call(RoleSeeder::class);

        // 2. Buat akun Owner default
        $owner = User::create([
            'name' => 'Owner Pemancingan',
            'email' => 'owner@pemancingan.com',
            'phone' => '081234567890',
            'password' => bcrypt('password'),
            'validation_status' => 'aktif',
        ]);
        $owner->assignRole('Owner');

        // 3. Buat akun Pegawai default
        $pegawai = User::create([
            'name' => 'Pegawai Satu',
            'email' => 'pegawai@pemancingan.com',
            'phone' => '081298765432',
            'password' => bcrypt('password'),
            'validation_status' => 'aktif',
        ]);
        $pegawai->assignRole('Pegawai');

        // 4. Seed data jenis ikan dengan threshold minimum
        FishType::create([
            'name' => 'Patin',
            'price_per_kg' => 35000,
            'stock_kg' => 100,
            'min_stock_threshold' => 50,
        ]);
        FishType::create([
            'name' => 'Gurame',
            'price_per_kg' => 50000,
            'stock_kg' => 30,
            'min_stock_threshold' => 10,
        ]);
        FishType::create([
            'name' => 'Nila',
            'price_per_kg' => 30000,
            'stock_kg' => 60,
            'min_stock_threshold' => 25,
        ]);
        FishType::create([
            'name' => 'Bawal',
            'price_per_kg' => 45000,
            'stock_kg' => 15,
            'min_stock_threshold' => 5,
        ]);
    }
}
