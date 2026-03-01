<?php

namespace App\Filament\Resources\FishTypeResource\Pages;

use App\Filament\Resources\FishTypeResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFishTypes extends ListRecords
{
    protected static string $resource = FishTypeResource::class;

    protected bool $isRestricted = false;

    public function mount(): void
    {
        $this->isRestricted = !auth()->user()?->can('manage-fish-stock');

        if ($this->isRestricted) {
            Notification::make()
                ->title('Akses Dibatasi')
                ->body('Anda tidak memiliki izin untuk mengelola stok ikan. Hubungi pemilik untuk mendapatkan akses.')
                ->danger()
                ->persistent()
                ->send();
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        if ($this->isRestricted) {
            return [];
        }

        return [
            Actions\CreateAction::make(),
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
