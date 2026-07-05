<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionSitio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Configuración del sitio';

    protected static ?string $title = 'Configuración del sitio';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.configuracion-sitio';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::allValues());
    }

    private function enfasisHint(): string
    {
        return 'Usa *palabra* para resaltarla (itálica/color). Los saltos de línea se respetan.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tabs\Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                        TextInput::make('seo_title')->label('Título (title)')->maxLength(70)
                            ->helperText('Aparece en la pestaña del navegador y en Google.'),
                        Textarea::make('seo_description')->label('Meta descripción')->rows(3)->maxLength(180)
                            ->helperText('Resumen para Google (ideal 150-160 caracteres).'),
                        FileUpload::make('seo_og_image')->label('Imagen para compartir (Open Graph)')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                    ]),

                    Tabs\Tab::make('Marca')->icon('heroicon-o-sparkles')->schema([
                        TextInput::make('brand_name')->label('Nombre de marca'),
                        TextInput::make('brand_tagline')->label('Tagline'),
                    ]),

                    Tabs\Tab::make('Hero')->icon('heroicon-o-photo')->schema([
                        TextInput::make('hero_eyebrow')->label('Antetítulo'),
                        Textarea::make('hero_title')->label('Título principal')->rows(3)->helperText($this->enfasisHint()),
                        Textarea::make('hero_body')->label('Texto')->rows(3),
                        TextInput::make('hero_cta1')->label('Botón principal'),
                        TextInput::make('hero_cta2')->label('Botón secundario'),
                        TextInput::make('hero_pill')->label('Etiqueta sobre la foto'),
                        FileUpload::make('hero_image')->label('Foto del hero')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                    ]),

                    Tabs\Tab::make('Misión')->icon('heroicon-o-flag')->schema([
                        TextInput::make('mission_label')->label('Etiqueta'),
                        Textarea::make('mission_text')->label('Texto')->rows(3)->helperText($this->enfasisHint()),
                    ]),

                    Tabs\Tab::make('Pilares')->icon('heroicon-o-squares-2x2')->schema([
                        TextInput::make('pillars_label')->label('Etiqueta'),
                        TextInput::make('pillars_heading')->label('Encabezado'),
                        ...$this->camposPilar(1),
                        ...$this->camposPilar(2),
                        ...$this->camposPilar(3),
                        ...$this->camposPilar(4),
                    ]),

                    Tabs\Tab::make('Sessions')->icon('heroicon-o-chat-bubble-left-right')->schema([
                        TextInput::make('sessions_label')->label('Etiqueta'),
                        Textarea::make('sessions_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('sessions_body')->label('Texto')->rows(3),
                        TextInput::make('sessions_cta')->label('Botón'),
                        TextInput::make('session_topic_1')->label('Tema 1'),
                        TextInput::make('session_topic_2')->label('Tema 2'),
                        TextInput::make('session_topic_3')->label('Tema 3'),
                        TextInput::make('session_topic_4')->label('Tema 4'),
                        TextInput::make('session_topic_5')->label('Tema 5'),
                    ]),

                    Tabs\Tab::make('Para quién')->icon('heroicon-o-user-group')->schema([
                        TextInput::make('forwho_label')->label('Etiqueta'),
                        Textarea::make('forwho_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('forwho_body')->label('Texto')->rows(3),
                        ...$this->camposTarjeta(1),
                        ...$this->camposTarjeta(2),
                        ...$this->camposTarjeta(3),
                    ]),

                    Tabs\Tab::make('Cita')->icon('heroicon-o-chat-bubble-bottom-center-text')->schema([
                        Textarea::make('quote_text')->label('Cita')->rows(3)->helperText($this->enfasisHint()),
                        TextInput::make('quote_attr')->label('Autor'),
                    ]),

                    Tabs\Tab::make('Únete')->icon('heroicon-o-user-plus')->schema([
                        TextInput::make('join_label')->label('Etiqueta'),
                        Textarea::make('join_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('join_body')->label('Texto')->rows(3),
                        TextInput::make('join_cta')->label('Botón principal'),
                        TextInput::make('join_note')->label('Nota'),
                        TextInput::make('join_tog1')->label('Opción 1'),
                        TextInput::make('join_tog2')->label('Opción 2'),
                        TextInput::make('join_tog3')->label('Opción 3'),
                    ]),

                    Tabs\Tab::make('Pie e imágenes')->icon('heroicon-o-bars-3-bottom-left')->schema([
                        TextInput::make('footer_tag')->label('Tagline del pie'),
                        TextInput::make('footer_copy')->label('Copyright'),
                        FileUpload::make('divider_image')->label('Foto divisora')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                    ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    private function camposPilar(int $n): array
    {
        return [
            TextInput::make("pillar{$n}_title")->label("Pilar $n · Título"),
            Textarea::make("pillar{$n}_body")->label("Pilar $n · Texto")->rows(2),
        ];
    }

    private function camposTarjeta(int $n): array
    {
        return [
            TextInput::make("card{$n}_label")->label("Tarjeta $n · Etiqueta"),
            TextInput::make("card{$n}_title")->label("Tarjeta $n · Título"),
            Textarea::make("card{$n}_body")->label("Tarjeta $n · Texto")->rows(2),
        ];
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SiteSetting::set($key, $value);
        }

        Notification::make()->title('Cambios guardados')->success()->send();
    }
}
