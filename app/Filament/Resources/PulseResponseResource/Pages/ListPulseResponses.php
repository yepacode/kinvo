<?php

namespace App\Filament\Resources\PulseResponseResource\Pages;

use App\Filament\Resources\PulseResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPulseResponses extends ListRecords
{
    protected static string $resource = PulseResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Registrar Pulso manual'),
        ];
    }
}
