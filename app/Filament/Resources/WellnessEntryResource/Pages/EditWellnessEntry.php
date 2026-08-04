<?php

namespace App\Filament\Resources\WellnessEntryResource\Pages;

use App\Filament\Resources\WellnessEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWellnessEntry extends EditRecord
{
    protected static string $resource = WellnessEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
