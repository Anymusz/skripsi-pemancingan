<?php

namespace App\Filament\Resources\MemberValidationResource\Pages;

use App\Filament\Resources\MemberValidationResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberValidations extends ListRecords
{
    protected static string $resource = MemberValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
