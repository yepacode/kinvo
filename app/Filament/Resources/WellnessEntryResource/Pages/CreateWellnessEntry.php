<?php

namespace App\Filament\Resources\WellnessEntryResource\Pages;

use App\Filament\Resources\WellnessEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWellnessEntry extends CreateRecord
{
    protected static string $resource = WellnessEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('← Volver al listado')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
