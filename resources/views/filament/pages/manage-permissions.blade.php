<x-filament-panels::page>
    @push('styles')
    <style>
        .permission-page-wrapper {
            font-family: 'Inter', sans-serif;
        }

        /* Role Selector */
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .role-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-radius: 14px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            min-width: 160px;
        }

        .dark .role-card {
            background: rgb(31 41 55);
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .role-card:hover:not(.active) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .role-card.active.pegawai  { border-color: #f59e0b; background: #fffbeb; }
        .role-card.active.member   { border-color: #10b981; background: #f0fdf4; }
        .role-card.active.guest    { border-color: #6b7280; background: #f9fafb; }

        .dark .role-card.active.pegawai { background: rgba(245,158,11,0.1); }
        .dark .role-card.active.member  { background: rgba(16,185,129,0.1); }
        .dark .role-card.active.guest   { background: rgba(107,114,128,0.1); }

        .role-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }

        .role-info h4 { font-size: 14px; font-weight: 600; margin: 0; color: #111827; }
        .role-info p  { font-size: 11px; margin: 0; color: #6b7280; }
        .dark .role-info h4 { color: #f9fafb; }

        /* Permission Panel */
        .permission-panel {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
        }

        .dark .permission-panel {
            background: rgb(31 41 55);
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .permission-panel-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dark .permission-panel-header { border-color: rgb(55 65 81); }

        .permission-panel-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .dark .permission-panel-header h3 { color: #f9fafb; }

        .perm-count-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Category Section */
        .category-section { border-bottom: 1px solid #f3f4f6; }
        .dark .category-section { border-color: rgb(55 65 81); }
        .category-section:last-child { border-bottom: none; }

        .category-header {
            padding: 10px 24px 8px;
            background: #fafafa;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dark .category-header { background: rgb(17 24 39); }

        .category-title {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .perm-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 24px;
            border-top: 1px solid #f9fafb;
            transition: background 0.15s;
            cursor: pointer;
        }

        .dark .perm-row { border-color: rgba(255,255,255,0.04); }
        .perm-row:hover { background: #fafafa; }
        .dark .perm-row:hover { background: rgba(255,255,255,0.02); }

        .perm-label {
            font-size: 13.5px;
            color: #374151;
        }

        .dark .perm-label { color: #d1d5db; }

        /* Toggle Switch */
        .toggle-wrap { position: relative; display: inline-flex; align-items: center; }
        .toggle-wrap input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }

        .toggle-track {
            width: 40px;
            height: 22px;
            border-radius: 11px;
            background: #e5e7eb;
            transition: background 0.2s ease;
            position: relative;
            flex-shrink: 0;
        }

        .dark .toggle-track { background: rgb(55 65 81); }

        .toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }

        .toggle-wrap input:checked + .toggle-track { background: #6366f1; }
        .toggle-wrap input:checked + .toggle-track .toggle-thumb { transform: translateX(18px); }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .dark .empty-state { background: rgb(31 41 55); }

        .empty-icon {
            width: 56px;
            height: 56px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }

        .dark .empty-icon { background: rgb(55 65 81); }

        /* Save Button */
        .save-section {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
    @endpush

    <div class="permission-page-wrapper">
        <form wire:submit="save">

            {{-- ===  Role Selector === --}}
            <div class="role-selector">
                @foreach($this->getRoles() as $role)
                    @php
                        $isActive  = $this->activeRole === (string) $role->id;
                        $roleKey   = "role_{$role->id}";
                        $permCount = count($this->data[$roleKey] ?? []);
                        $total     = \Spatie\Permission\Models\Permission::count();
                        $colorClass = match($role->name) {
                            'Pegawai' => 'pegawai',
                            'Member'  => 'member',
                            'Guest'   => 'guest',
                            default   => 'guest',
                        };
                        $avatarBg = match($role->name) {
                            'Pegawai' => '#f59e0b',
                            'Member'  => '#10b981',
                            'Guest'   => '#6b7280',
                            default   => '#6366f1',
                        };
                    @endphp
                    <button
                        type="button"
                        wire:click="toggleRole('{{ $role->id }}')"
                        class="role-card {{ $colorClass }} {{ $isActive ? 'active' : '' }}"
                    >
                        <div class="role-avatar" style="background: {{ $avatarBg }}">
                            {{ strtoupper(substr($role->name, 0, 1)) }}
                        </div>
                        <div class="role-info">
                            <h4>{{ $role->name }}</h4>
                            <p>{{ $permCount }} / {{ $total }} akses</p>
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- === Permission Panel === --}}
            @if($this->activeRole)
                @php
                    $activeRoleObj = \Spatie\Permission\Models\Role::find($this->activeRole);
                    $roleKey       = "role_{$this->activeRole}";
                    $permCount     = count($this->data[$roleKey] ?? []);
                    $total         = \Spatie\Permission\Models\Permission::count();
                    $badgeColor    = match($activeRoleObj?->name) {
                        'Pegawai' => 'background:#fef3c7;color:#92400e',
                        'Member'  => 'background:#d1fae5;color:#065f46',
                        'Guest'   => 'background:#f3f4f6;color:#374151',
                        default   => 'background:#ede9fe;color:#5b21b6',
                    };
                @endphp

                <div class="permission-panel">
                    <div class="permission-panel-header">
                        <h3>Hak Akses — {{ $activeRoleObj?->name }}</h3>
                        <span class="perm-count-badge" style="{{ $badgeColor }}">
                            {{ $permCount }} aktif dari {{ $total }}
                        </span>
                    </div>

                    @foreach($this->getGroupedPermissions() as $category => $permissions)
                        <div class="category-section">
                            <div class="category-header">
                                <span class="category-title">{{ $category }}</span>
                            </div>

                            @foreach($permissions as $permId => $permLabel)
                                <label class="perm-row">
                                    <span class="perm-label">{{ $permLabel }}</span>
                                    <div class="toggle-wrap">
                                        <input
                                            type="checkbox"
                                            wire:model.defer="data.{{ $roleKey }}"
                                            value="{{ $permId }}"
                                            id="perm-{{ $this->activeRole }}-{{ $permId }}"
                                        >
                                        <div class="toggle-track">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="save-section">
                    <x-filament::button type="submit" size="lg">
                        Simpan Perubahan
                    </x-filament::button>
                    <span class="text-sm text-gray-400">Perubahan berlaku setelah disimpan</span>
                </div>

            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p style="font-size:14px;color:#9ca3af;margin:0">Pilih role di atas untuk mengatur hak akses</p>
                </div>
            @endif

        </form>
    </div>
</x-filament-panels::page>
