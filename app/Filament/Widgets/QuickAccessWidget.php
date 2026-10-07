<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Models\QuickAccessLink;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class QuickAccessWidget extends Widget implements HasActions, HasForms
{
    use HasWidgetShield, InteractsWithActions, InteractsWithForms;

    protected static string $view = 'filament.widgets.quick-access-widget';
    protected static ?int $sort = -20;
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Accesos Rápidos';
    }

    public static function iconOptions(): array
    {
        return [
            'heroicon-o-link' => 'Enlace',
            'heroicon-o-home' => 'Inicio',
            'heroicon-o-user-group' => 'Personas',
            'heroicon-o-identification' => 'Identificación',
            'heroicon-o-document-text' => 'Formularios',
            'heroicon-o-clipboard-document-check' => 'Control',
            'heroicon-o-archive-box-arrow-down' => 'Paquetes',
            'heroicon-o-truck' => 'Vehículos',
            'heroicon-o-calendar-days' => 'Calendario',
            'heroicon-o-chat-bubble-left-right' => 'Mensajes',
            'heroicon-o-bell-alert' => 'Alertas',
            'heroicon-o-chart-bar' => 'Monitor',
            'heroicon-o-cog-6-tooth' => 'Configuración',
            'heroicon-o-globe-alt' => 'Sitio web',
        ];
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->label('Configurar')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading('Configurar accesos rápidos')
            ->modalDescription('Agregue, edite, quite o reordene los enlaces que quiere tener a mano.')
            ->modalSubmitActionLabel('Guardar accesos')
            ->fillForm(fn (): array => [
                'links' => auth()->user()->quickAccessLinks()->get()
                    ->map(fn (QuickAccessLink $link) => [
                        'id' => $link->id,
                        'name' => $link->name,
                        'url' => $link->url,
                        'icon' => $link->icon,
                    ])->all(),
            ])
            ->form([
                Forms\Components\Repeater::make('links')
                    ->label('Enlaces')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')->required()->maxLength(80),
                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->placeholder('/form-controls o https://sitio.com')
                            ->helperText('Puede usar una ruta del sistema que comience con / o una dirección https:// completa.')
                            ->required()->maxLength(2048)
                            ->rule('regex:/^(\/(?!\/)|https?:\/\/)/i'),
                        Forms\Components\Select::make('icon')
                            ->label('Icono')->options(static::iconOptions())
                            ->default('heroicon-o-link')->required()->native(false),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addActionLabel('Agregar enlace')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): string => $state['name'] ?? 'Nuevo acceso')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $user = auth()->user();
                $links = collect($data['links'] ?? [])->values();

                DB::transaction(function () use ($user, $links): void {
                    $ownedIds = $user->quickAccessLinks()->pluck('id')->map(fn ($id) => (int) $id);
                    $submittedIds = $links->pluck('id')->filter()
                        ->map(fn ($id) => (int) $id)->intersect($ownedIds);

                    $user->quickAccessLinks()->whereNotIn('id', $submittedIds)->delete();

                    foreach ($links as $position => $linkData) {
                        $attributes = [
                            'name' => trim($linkData['name']),
                            'url' => trim($linkData['url']),
                            'icon' => array_key_exists($linkData['icon'], static::iconOptions())
                                ? $linkData['icon']
                                : 'heroicon-o-link',
                            'sort_order' => $position,
                        ];
                        $id = isset($linkData['id']) ? (int) $linkData['id'] : null;

                        if ($id && $ownedIds->contains($id)) {
                            $user->quickAccessLinks()->whereKey($id)->update($attributes);
                        } else {
                            $user->quickAccessLinks()->create($attributes);
                        }
                    }
                });

                Notification::make()->title('Accesos rápidos actualizados')->success()->send();
            });
    }

    public function getViewData(): array
    {
        $icons = static::iconOptions();

        return [
            'links' => auth()->user()->quickAccessLinks()->get()->map(fn (QuickAccessLink $link) => [
                'name' => $link->name,
                'url' => $link->url,
                'icon' => array_key_exists($link->icon, $icons) ? $link->icon : 'heroicon-o-link',
                'external' => str_starts_with($link->url, 'http://') || str_starts_with($link->url, 'https://'),
            ]),
        ];
    }
}
