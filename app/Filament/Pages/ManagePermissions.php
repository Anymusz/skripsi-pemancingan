<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Owner') ?? false;
    }

    public ?array $data = [];

    // Track role mana yang sedang dibuka
    public ?string $activeRole = null;

    public function mount(): void
    {
        $roles = Role::where('name', '!=', 'Owner')->get();
        foreach ($roles as $role) {
            $this->data["role_{$role->id}"] = $role->permissions->pluck('id')->toArray();
        }
    }

    public function getTitle(): string
    {
        return 'Kelola Akses Role';
    }

    public function toggleRole(string $roleId): void
    {
        $this->activeRole = ($this->activeRole === $roleId) ? null : $roleId;
    }

    public function getRoles()
    {
        return Role::where('name', '!=', 'Owner')->get();
    }

    /**
     * Permissions dikelompokkan per kategori (tanpa emoji).
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $perm) {
            $category = match (true) {
                str_contains($perm->name, 'user') || str_contains($perm->name, 'member') => 'Manajemen User',
                str_contains($perm->name, 'transaction') || str_contains($perm->name, 'checkout') => 'Transaksi',
                str_contains($perm->name, 'fish') || str_contains($perm->name, 'restock') => 'Stok Ikan',
                str_contains($perm->name, 'menu') || str_contains($perm->name, 'fnb') => 'Menu & F&B',
                str_contains($perm->name, 'event') || str_contains($perm->name, 'publish') => 'Event',
                str_contains($perm->name, 'report') || str_contains($perm->name, 'export') => 'Laporan',
                str_contains($perm->name, 'guest') || str_contains($perm->name, 'activate') => 'Tamu',
                str_contains($perm->name, 'leaderboard') => 'Leaderboard',
                str_contains($perm->name, 'profile') => 'Profil',
                default => 'Lainnya',
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

    public function save(): void
    {
        $roles = Role::where('name', '!=', 'Owner')->get();

        foreach ($roles as $role) {
            $key = "role_{$role->id}";
            $permissionIds = $this->data[$key] ?? [];
            $permissions = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title('Akses berhasil diperbarui!')
            ->success()
            ->send();
    }
}
