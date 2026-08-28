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
                Forms\Components\Select::make('type')->label('Tipo')->required()->live()
                    ->options([
                        ContentItem::TYPE_VIDEO => 'Video',
                        ContentItem::TYPE_IMAGE => 'Imagen',
                        ContentItem::TYPE_BLOG => 'Blog / artículo',
                        ContentItem::TYPE_AUDIO => 'Audio',
                        ContentItem::TYPE_DOCUMENT => 'Documento',
                        ContentItem::TYPE_LINK => 'Enlace',
                    ])->default(ContentItem::TYPE_VIDEO),
                Forms\Components\Textarea::make('description')->label('Descripción / resumen')
                    ->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Contenido')->schema([
                // Blog: cuerpo con texto enriquecido.
                Forms\Components\RichEditor::make('body')->label('Cuerpo del artículo')
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('type') === ContentItem::TYPE_BLOG),

                // Subida real de archivo → disco PRIVADO ('local'). Se sirve por
                // la ruta gateada contenido.archivo; el link directo no existe.
                Forms\Components\FileUpload::make('file_path')->label('Archivo (subir)')
                    ->disk('local')->directory('contenido')->visibility('private')
                    ->acceptedFileTypes(fn (Forms\Get $get) => match ($get('type')) {
                        ContentItem::TYPE_VIDEO => ['video/mp4', 'video/webm', 'video/quicktime'],
                        ContentItem::TYPE_IMAGE => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        ContentItem::TYPE_AUDIO => ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg'],
                        ContentItem::TYPE_DOCUMENT => ['application/pdf'],
                        default => null,
                    })
                    // Feedback Karla 27-ago: 40 MB no alcanzaba para clases.
                    // Subido a 128 MB (default Hostinger Business con el `.user.ini`
                    // que Kinvoo publica en public/). Para clases largas conviene YouTube.
                    ->maxSize(131072)
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('file_disk', 'local'))
                    ->helperText('Hasta 128 MB. Para clases largas (más de 15 min) recomendamos subirlas a YouTube o Vimeo (privadas o no listadas) y pegar el link en el campo de URL externa — es más rápido y no consume espacio del servidor.')
                    ->visible(fn (Forms\Get $get) => in_array($get('type'), [
                        ContentItem::TYPE_VIDEO, ContentItem::TYPE_IMAGE,
                        ContentItem::TYPE_AUDIO, ContentItem::TYPE_DOCUMENT,
                    ], true)),

                // URL externa: embeds / enlaces / videos pesados.
                Forms\Components\TextInput::make('url')->label('… o URL externa (YouTube/Vimeo/enlace)')
                    ->url()->maxLength(500)->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('type') !== ContentItem::TYPE_BLOG),

                Forms\Components\Hidden::make('file_disk')->default('local'),
            ]),

            Forms\Components\Section::make('¿Para quién? — rol + nivel + membresía')->schema([
                Forms\Components\Select::make('gate_role')->label('Rol')
                    ->options([
                        'professional' => 'Solo profesionales (coaches)',
                        'contractor' => 'Solo estudios',
                    ])
                    ->placeholder('Abierto a ambos'),
                Forms\Components\Select::make('access_level')->label('Nivel')
                    ->options([
                        1 => 'Gratis (nivel 1)',
                        2 => 'Premium (nivel 2)',
                        3 => 'Premium+ (nivel 3)',
                    ])->default(1)->required()->selectablePlaceholder(false)
                    ->helperText('Nivel 2 o 3 exige membresía activa.'),
                Forms\Components\Select::make('gate_plan_id')->label('Plan específico (opcional)')
                    ->relationship('gatePlan', 'nombre')
                    ->placeholder('Cualquiera (según nivel/rol)')
                    ->helperText('Si eliges un plan, SOLO los miembros de ese plan exacto lo verán.'),
            ])->columns(3),

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
                        ContentItem::TYPE_IMAGE => 'Imagen',
                        ContentItem::TYPE_BLOG => 'Blog',
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
                    ContentItem::TYPE_IMAGE => 'Imagen',
                    ContentItem::TYPE_BLOG => 'Blog',
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
