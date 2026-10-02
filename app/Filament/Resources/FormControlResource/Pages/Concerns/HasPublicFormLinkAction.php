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
            ->modalDescription('Elegí el lote y para quién se generará el formulario.')
            ->modalSubmitActionLabel('Generar enlace')
            ->form([
                Forms\Components\Select::make('lote_id')
                    ->label('Lote')
                    ->options(fn () => auth()->user()->owner->lotes->mapWithKeys(fn (Lote $lote) => [$lote->id => $lote->getNombre()]))
                    ->default(fn () => auth()->user()->owner->lotes->count() === 1 ? auth()->user()->owner->lotes->first()->id : null)
                    ->required(),
                Forms\Components\CheckboxList::make('income_type')
                    ->label('Tipo de formulario')
                    ->options(FormControlPublicInvitation::publicIncomeTypes())
                    ->descriptions([
                        FormControlPublicInvitation::TENANT => 'Para que el inquilino cargue sus datos, documentación y vehículos.',
                        FormControlPublicInvitation::TENANT_VISITOR => 'Para registrar a una persona que visitará al inquilino durante su estadía.',
                    ])
                    ->default([FormControlPublicInvitation::TENANT])
                    ->minItems(1)
                    ->maxItems(1)
                    ->required()
                    ->helperText('Este enlace es exclusivamente para inquilinos o para sus visitantes. Para trabajadores, proveedores u otros accesos, utilizá el formulario correspondiente.'),
            ])
            ->action(function (array $data, $livewire): void {
                $lote = auth()->user()->owner->lotes()->findOrFail($data['lote_id']);
                $incomeType = $data['income_type'][0] ?? FormControlPublicInvitation::TENANT;
                [, $token] = FormControlPublicInvitation::issue(auth()->user()->owner, $lote, auth()->user(), $incomeType);

                $livewire->replaceMountedAction('generatedPublicLink', [
                    'url' => route('form-control-public.show', $token),
                    'lot' => $lote->getNombre(),
                    'incomeType' => $incomeType,
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
                'incomeType' => $arguments['incomeType'] ?? FormControlPublicInvitation::TENANT,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar, ya lo guardé')
            ->closeModalByClickingAway(false);
    }
}
