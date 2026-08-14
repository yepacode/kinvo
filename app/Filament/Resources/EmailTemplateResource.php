<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * M7 · Editor de correos (petición cliente). Marian edita cada plantilla
 * sin tocar código. Los placeholders `{{key}}` se reemplazan al enviar.
 */
class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $modelLabel = 'plantilla';
    protected static ?string $pluralModelLabel = 'Plantillas de correo';
    protected static ?int $navigationSort = 26;

    public static function canCreate(): bool
    {
        return false; // Se crean via seeder; el admin solo edita.
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identidad')
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Clave técnica')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('No editable — la referencia usa esta clave desde el código.'),
                    Forms\Components\TextInput::make('description')
                        ->label('¿Qué correo es?')
                        ->required()
                        ->maxLength(200),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Plantilla activa')
                        ->helperText('Si la desactivas, el correo cae al texto por defecto (hard-coded).')
                        ->default(true),
                    Forms\Components\Placeholder::make('placeholders')
                        ->label('Variables disponibles')
                        ->content(fn (?EmailTemplate $record) => $record?->placeholders_hint
                            ? collect($record->placeholders_hint)->map(fn ($p) => "{{".$p."}}")->implode('  ·  ')
                            : '—'),
                ])->columns(2),

            Forms\Components\Section::make('Contenido del correo')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->label('Asunto')
                        ->required()
                        ->maxLength(200)
                        ->helperText('Puedes usar variables. Ej: "Kinvoo · {{estudio}} te invita"'),
                    Forms\Components\TextInput::make('greeting')
                        ->label('Saludo')
                        ->maxLength(200)
                        ->placeholder('Hola {{coach}},'),
                    Forms\Components\Textarea::make('body')
                        ->label('Cuerpo')
                        ->required()
                        ->rows(6)
                        ->helperText('Puedes usar **negritas** con asteriscos. Las variables van entre llaves dobles.'),
                    Forms\Components\TextInput::make('action_label')
                        ->label('Texto del botón (opcional)')
                        ->maxLength(60)
                        ->placeholder('Ver invitación'),
                    Forms\Components\Textarea::make('outro')
                        ->label('Línea de cierre (opcional)')
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => EmailTemplate::query())
            ->defaultSort('key')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Correo')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')->wrap()->limit(60)->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última edición')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar correo'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'edit'  => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
