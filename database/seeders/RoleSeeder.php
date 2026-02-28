<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Seed roles dan permissions untuk sistem pemancingan.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================================
        // BUAT PERMISSIONS
        // ========================================

        // User Management
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'validate-members']);

        // Transaction
        Permission::create(['name' => 'create-transaction']);
        Permission::create(['name' => 'process-checkout']);
        Permission::create(['name' => 'view-transactions']);

        // Fish Stock
        Permission::create(['name' => 'manage-fish-stock']);
        Permission::create(['name' => 'restock-fish']);

        // Menu
        Permission::create(['name' => 'manage-menus']);

        // Events
        Permission::create(['name' => 'manage-events']);
        Permission::create(['name' => 'publish-event']);

        // Reports
        Permission::create(['name' => 'view-reports']);
        Permission::create(['name' => 'export-reports']);

        // Guest
        Permission::create(['name' => 'activate-guest']);

        // Member Features
        Permission::create(['name' => 'view-leaderboard']);
        Permission::create(['name' => 'order-fnb']);
        Permission::create(['name' => 'view-own-profile']);
        Permission::create(['name' => 'view-own-transactions']);

        // ========================================
        // BUAT ROLES & ASSIGN PERMISSIONS
        // ========================================

        // Owner - Akses penuh
        $owner = Role::create(['name' => 'Owner']);
        $owner->givePermissionTo(Permission::all());

        // Pegawai - Bantu operasional
        $pegawai = Role::create(['name' => 'Pegawai']);
        $pegawai->givePermissionTo([
            'create-transaction',
            'process-checkout',
            'view-transactions',
            'activate-guest',
            'manage-fish-stock',
            'manage-menus',
            'view-leaderboard',
            'order-fnb',
        ]);

        // Member - Fitur pelanggan tetap
        $member = Role::create(['name' => 'Member']);
        $member->givePermissionTo([
            'view-leaderboard',
            'order-fnb',
            'view-own-profile',
            'view-own-transactions',
        ]);

        // Guest - Fitur terbatas (hanya pesan F&B)
        $guest = Role::create(['name' => 'Guest']);
        $guest->givePermissionTo([
            'order-fnb',
        ]);
    }
}
