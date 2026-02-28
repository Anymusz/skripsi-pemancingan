<?php
namespace App\Filament\Resources\FishTypeResource\Pages;
use App\Filament\Resources\FishTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFishTypes extends ListRecords
{
    protected static string $resource = FishTypeResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
