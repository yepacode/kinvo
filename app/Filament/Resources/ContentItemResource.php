<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentItemResource\Pages;
use App\Models\ContentItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContentItemResource extends Resource
{
    protected static ?string $model = ContentItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Contenido';
    protected static ?string $modelLabel = 'contenido';
    protected static ?string $pluralModelLabel = 'Contenidos';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identidad')->schema([
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(180),
                Forms\Components\TextInput::make('slug')->label('Slug (opcional, se autogenera)')
                    ->helperText('Déjalo vacío para generarlo del título.')->maxLength(220),
                Forms\Components\TextInput::make('category')->label('Categoría')
                    ->placeholder('Ej: entrenamiento, nutrición, mindfulness'),
                Forms\Components\Select::make('type')->label('Tipo')->required()
                    ->options([
                        ContentItem::TYPE_VIDEO => 'Video',
                        ContentItem::TYPE_DOCUMENT => 'Documento',
                        ContentItem::TYPE_AUDIO => 'Audio',
                        ContentItem::TYPE_LINK => 'Enlace',
                    ])->default(ContentItem::TYPE_VIDEO),
                Forms\Components\Textarea::make('description')->label('Descripción')
                    ->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Ubicación del recurso')->schema([
                Forms\Components\TextInput::make('url')->label('URL externa (Vimeo/YouTube/enlace)')
                    ->url()->maxLength(500),
                Forms\Components\TextInput::make('file_path')->label('Archivo interno (path)')
                    ->helperText('Usar url o file_path — no ambos.')->maxLength(500),
            ])->columns(2),

            Forms\Components\Section::make('Restricciones de acceso (gate)')->schema([
                Forms\Components\Select::make('gate_role')->label('Rol requerido')
                    ->options([
                        'professional' => 'Solo profesionales (coaches)',
                        'contractor' => 'Solo estudios',
                    ])
                    ->placeholder('Abierto a ambos'),
                Forms\Components\Select::make('gate_plan_id')->label('Plan mínimo requerido')
                    ->relationship('gatePlan', 'nombre')
                    ->placeholder('Sin plan requerido'),
            ])->columns(2),

            Forms\Components\Section::make('Publicación')->schema([
                Forms\Components\Toggle::make('is_published')->label('Publicado')->default(false),
                Forms\Components\DateTimePicker::make('published_at')->label('Publicado el'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')
                    ->description(fn ($record) => $record?->category ?: '—')
                    ->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('type')->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        ContentItem::TYPE_VIDEO => 'Video',
                        ContentItem::TYPE_DOCUMENT => 'Documento',
                        ContentItem::TYPE_AUDIO => 'Audio',
                        ContentItem::TYPE_LINK => 'Enlace',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('gate_role')->label('Restringido a')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'professional' => 'Coaches',
                        'contractor' => 'Estudios',
                        default => 'Abierto',
                    }),
                Tables\Columns\TextColumn::make('gatePlan.nombre')->label('Plan requerido')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_published')->label('Publicado')->boolean(),
                Tables\Columns\TextColumn::make('views_count')->label('Vistas')
                    ->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('published_at')->label('Publicado el')
                    ->dateTime('d/m/Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options([
                    ContentItem::TYPE_VIDEO => 'Video',
                    ContentItem::TYPE_DOCUMENT => 'Documento',
                    ContentItem::TYPE_AUDIO => 'Audio',
                    ContentItem::TYPE_LINK => 'Enlace',
                ]),
                SelectFilter::make('gate_role')->label('Rol requerido')->options([
                    'professional' => 'Coaches',
                    'contractor' => 'Estudios',
                ]),
                TernaryFilter::make('is_published')->label('Publicación')
                    ->placeholder('Todos')->trueLabel('Publicado')->falseLabel('Borrador'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentItems::route('/'),
            'create' => Pages\CreateContentItem::route('/create'),
            'edit' => Pages\EditContentItem::route('/{record}/edit'),
        ];
    }
}
