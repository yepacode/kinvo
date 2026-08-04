<?php

namespace App\Filament\Resources\WellnessEntryResource\Pages;

use App\Filament\Resources\WellnessEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWellnessEntries extends ListRecords
{
    protected static string $resource = WellnessEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
