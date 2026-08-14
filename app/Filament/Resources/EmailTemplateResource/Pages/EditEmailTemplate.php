<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function afterSave(): void
    {
        // Cambios en plantillas de correo se auditan (bitácora legal).
        AuditLog::record(
            auth()->user(),
            $this->record,
            'email_template_edited',
            new: ['key' => $this->record->key, 'subject' => $this->record->subject]
        );
    }
}
