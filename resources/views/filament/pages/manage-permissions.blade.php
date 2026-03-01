<x-filament-panels::page>
    <form wire:submit="save">
        <div class="space-y-6">
            @foreach($this->getRoles() as $role)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::badge
                                :color="match($role->name) {
                                    'Pegawai' => 'warning',
                                    'Member' => 'success',
                                    'Guest' => 'gray',
                                    default => 'primary',
                                }"
                            >
                                {{ $role->name }}
                            </x-filament::badge>
                            <span>— Atur permission untuk role ini</span>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($this->getGroupedPermissions() as $category => $permissions)
                            <div class="space-y-2">
                                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                                    {{ $category }}
                                </h4>
                                <div class="space-y-1">
                                    @foreach($permissions as $permId => $permLabel)
                                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1 transition">
                                            <input
                                                type="checkbox"
                                                wire:model.defer="data.role_{{ $role->id }}"
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
                </x-filament::section>
            @endforeach
        </div>

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                💾 Simpan Perubahan Akses
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
