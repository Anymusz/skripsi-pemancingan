<x-filament-panels::page>
    <form wire:submit="save">
        <div class="space-y-3">
            @foreach($this->getRoles() as $role)
                @php
                    $roleKey = "role_{$role->id}";
                    $isActive = $this->activeRole === (string) $role->id;
                    $permCount = count($this->data[$roleKey] ?? []);
                    $totalPerms = \Spatie\Permission\Models\Permission::count();
                @endphp

                {{-- Clickable Role Header --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button
                        type="button"
                        wire:click="toggleRole('{{ $role->id }}')"
                        class="w-full flex items-center justify-between px-5 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm
                                {{ match($role->name) {
                                    'Pegawai' => 'bg-amber-500',
                                    'Member' => 'bg-emerald-500',
                                    'Guest' => 'bg-gray-400',
                                    default => 'bg-blue-500',
                                } }}
                            ">
                                {{ strtoupper(substr($role->name, 0, 1)) }}
                            </div>
                            <div class="text-left">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</div>
                                <div class="text-xs text-gray-500">{{ $permCount }} dari {{ $totalPerms }} akses aktif</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform {{ $isActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Permission Checkboxes (collapsible) --}}
                    @if($isActive)
                        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4 bg-gray-50 dark:bg-gray-900">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach($this->getGroupedPermissions() as $category => $permissions)
                                    <div>
                                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ $category }}</div>
                                        <div class="space-y-1">
                                            @foreach($permissions as $permId => $permLabel)
                                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white dark:hover:bg-gray-800 rounded px-2 py-1.5 transition">
                                                    <input
                                                        type="checkbox"
                                                        wire:model.defer="data.{{ $roleKey }}"
                                                        value="{{ $permId }}"
                                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                    >
                                                    <span class="text-gray-700 dark:text-gray-300">{{ $permLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Simpan Perubahan Akses
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
