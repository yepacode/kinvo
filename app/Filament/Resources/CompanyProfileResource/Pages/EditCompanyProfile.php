<?php

namespace App\Filament\Resources\CompanyProfileResource\Pages;

use App\Filament\Resources\CompanyProfileResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\EditRecord;

class EditCompanyProfile extends EditRecord
{
    protected static string $resource = CompanyProfileResource::class;

    protected function afterSave(): void
    {
        AuditLog::record(
            auth()->user(),
            $this->record,
            'company_slots_updated',
            new: ['max_coach_slots' => $this->record->max_coach_slots]
        );
    }
}
