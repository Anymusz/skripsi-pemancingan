<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Actions\Action;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ManagePermissions extends Page
{
    protected string $view = 'filament.pages.manage-permissions';

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kelola Akses';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    /**
     * Hanya Owner yang bisa akses halaman ini.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Owner') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        // Load permission yang sudah dimiliki tiap role
        $roles = Role::where('name', '!=', 'Owner')->get();

        foreach ($roles as $role) {
            $this->data["role_{$role->id}"] = $role->permissions->pluck('id')->toArray();
        }
    }

    public function getTitle(): string
    {
        return 'Kelola Akses Role';
    }

    /**
     * Ambil semua role selain Owner.
     */
    public function getRoles()
    {
        return Role::where('name', '!=', 'Owner')->get();
    }

    /**
     * Ambil semua permissions, dikelompokkan.
     */
    public function getPermissionOptions(): array
    {
        return Permission::all()->pluck('name', 'id')->toArray();
    }

    /**
     * Ambil permissions dikelompokkan per kategori.
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $perm) {
            $parts = explode('-', $perm->name, 2);
            $category = match ($parts[0]) {
                'manage' => '👤 Manajemen',
                'validate' => '👤 Manajemen',
                'create', 'process', 'view' => '💰 Transaksi & Laporan',
                'export' => '💰 Transaksi & Laporan',
                'restock' => '🐟 Inventaris',
                'publish' => '📢 Event',
                'activate' => '🎫 Tamu',
                'order' => '🍔 F&B',
                default => '🔧 Lainnya',
            };

            $label = match ($perm->name) {
                'manage-users' => 'Kelola User',
                'validate-members' => 'Validasi Member',
                'create-transaction' => 'Buat Transaksi',
                'process-checkout' => 'Proses Checkout',
                'view-transactions' => 'Lihat Transaksi',
                'manage-fish-stock' => 'Kelola Stok Ikan',
                'restock-fish' => 'Restock Ikan',
                'manage-menus' => 'Kelola Menu F&B',
                'manage-events' => 'Kelola Event',
                'publish-event' => 'Publish Event',
                'view-reports' => 'Lihat Laporan',
                'export-reports' => 'Export Laporan',
                'activate-guest' => 'Aktivasi Tamu',
                'view-leaderboard' => 'Lihat Leaderboard',
                'order-fnb' => 'Pesan F&B',
                'view-own-profile' => 'Lihat Profil Sendiri',
                'view-own-transactions' => 'Lihat Transaksi Sendiri',
                default => $perm->name,
            };

            $grouped[$category][$perm->id] = $label;
        }

        return $grouped;
    }

    /**
     * Simpan perubahan permissions.
     */
    public function save(): void
    {
        $roles = Role::where('name', '!=', 'Owner')->get();

        foreach ($roles as $role) {
            $key = "role_{$role->id}";
            $permissionIds = $this->data[$key] ?? [];

            $permissions = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $role->syncPermissions($permissions);
        }

        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title('Akses berhasil diperbarui!')
            ->success()
            ->send();
    }
}
