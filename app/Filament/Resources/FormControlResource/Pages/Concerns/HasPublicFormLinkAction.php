<?php

namespace App\Filament\Resources\FormControlResource\Pages\Concerns;

use App\Filament\Resources\FormControlResource;
use App\Models\FormControlPublicInvitation;
use App\Models\Lote;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;

trait HasPublicFormLinkAction
{
    public function sharePublicFormAction(): Action
    {
        return Action::make('sharePublicForm')
            ->label('Generar enlace público')
            ->icon('heroicon-o-share')
            ->color('info')
            ->visible(fn () => FormControlResource::canCreate() && auth()->user()->hasRole('owner') && auth()->user()->owner_id)
            ->modalHeading('Crear enlace para completar el formulario')
            ->modalDescription('Elegí el lote. La persona invitada completará las fechas, personas, documentos, vehículos y demás información.')
            ->modalSubmitActionLabel('Generar enlace')
            ->form([
                Forms\Components\Select::make('lote_id')
                    ->label('Lote')
                    ->options(fn () => auth()->user()->owner->lotes->mapWithKeys(fn (Lote $lote) => [$lote->id => $lote->getNombre()]))
                    ->default(fn () => auth()->user()->owner->lotes->count() === 1 ? auth()->user()->owner->lotes->first()->id : null)
                    ->required(),
            ])
            ->action(function (array $data, $livewire): void {
                $lote = auth()->user()->owner->lotes()->findOrFail($data['lote_id']);
                [, $token] = FormControlPublicInvitation::issue(auth()->user()->owner, $lote, auth()->user());

                $livewire->replaceMountedAction('generatedPublicLink', [
                    'url' => route('form-control-public.show', $token),
                    'lot' => $lote->getNombre(),
                ]);
            });
    }

    public function generatedPublicLinkAction(): Action
    {
        return Action::make('generatedPublicLink')
            ->visible(fn () => FormControlResource::canCreate() && auth()->user()->hasRole('owner') && auth()->user()->owner_id)
            ->modalHeading('¡Tu enlace está listo!')
            ->modalWidth(MaxWidth::Large)
            ->modalContent(fn (array $arguments): View => view('filament.form-controls.public-link-modal', [
                'url' => $arguments['url'],
                'lot' => $arguments['lot'],
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar, ya lo guardé')
            ->closeModalByClickingAway(false);
    }
}
