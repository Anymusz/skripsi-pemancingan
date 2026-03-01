<x-filament-panels::page>
    <form wire:submit="save">

        {{-- Role Selector Tabs --}}
        <div class="flex items-center gap-2 mb-6 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl w-fit">
            @foreach($this->getRoles() as $role)
                @php
                    $isActive = $this->activeRole === (string) $role->id;
                    $permCount = count($this->data["role_{$role->id}"] ?? []);
                    $totalPerms = \Spatie\Permission\Models\Permission::count();
                @endphp
                <button
                    type="button"
                    wire:click="toggleRole('{{ $role->id }}')"
                    class="flex items-center gap-2.5 px-5 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                        {{ $isActive
                            ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-600'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        }}"
                >
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white
                        {{ match($role->name) {
                            'Pegawai' => 'bg-amber-500',
                            'Member' => 'bg-emerald-500',
                            'Guest' => 'bg-gray-400',
                            default => 'bg-blue-500',
                        } }}
                    ">
                        {{ strtoupper(substr($role->name, 0, 1)) }}
                    </span>
                    <div class="text-left">
                        <div>{{ $role->name }}</div>
                        @if($isActive)
                            <div class="text-[10px] text-gray-400 -mt-0.5">{{ $permCount }}/{{ $totalPerms }} akses</div>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Permission Table --}}
        @if($this->activeRole)
            @php $roleKey = "role_{$this->activeRole}"; @endphp

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                @foreach($this->getGroupedPermissions() as $category => $permissions)
                    {{-- Category Header --}}
                    <div class="px-5 py-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $category }}</span>
                    </div>

                    {{-- Permission Rows --}}
                    @foreach($permissions as $permId => $permLabel)
                        @php
                            $isChecked = in_array($permId, $this->data[$roleKey] ?? []);
                        @endphp
                        <label class="flex items-center justify-between px-5 py-3 border-b border-gray-50 dark:border-gray-700/50 cursor-pointer hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors group">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                                {{ $permLabel }}
                            </span>
                            <div class="relative">
                                <input
                                    type="checkbox"
                                    wire:model.defer="data.{{ $roleKey }}"
                                    value="{{ $permId }}"
                                    class="sr-only peer"
                                >
                                {{-- Toggle Switch --}}
                                <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:bg-primary-500 transition-colors"></div>
                                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-4 transition-transform"></div>
                            </div>
                        </label>
                    @endforeach
                @endforeach
            </div>

            <div class="mt-5">
                <x-filament::button type="submit" size="lg">
                    Simpan Perubahan
                </x-filament::button>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                    <x-heroicon-o-cursor-arrow-rays class="w-6 h-6 text-gray-400"/>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pilih role di atas untuk mengatur akses</p>
            </div>
        @endif

    </form>
</x-filament-panels::page>
