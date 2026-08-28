<?php

namespace App\Filament\Resources\PulseResponseResource\Pages;

use App\Filament\Resources\PulseResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePulseResponse extends CreateRecord
{
    protected static string $resource = PulseResponseResource::class;

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
