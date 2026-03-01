<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected bool $isRestricted = false;

    public function mount(): void
    {
        $this->isRestricted = !auth()->user()?->can('manage-events');

        if ($this->isRestricted) {
            Notification::make()
                ->title('Akses Dibatasi')
                ->body('Anda tidak memiliki izin untuk mengelola event & informasi. Hubungi pemilik untuk mendapatkan akses.')
                ->danger()
                ->persistent()
                ->send();
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        if ($this->isRestricted) return [];

        return [
            Actions\CreateAction::make()
                ->label('Tambah Event / Informasi'),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        if ($this->isRestricted) {
            return parent::getTableQuery()->whereRaw('1 = 0');
        }
        return parent::getTableQuery();
    }
}
