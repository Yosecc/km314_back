<?php

namespace App\Filament\Resources\FormControlResource\Pages\Concerns;

use App\Filament\Resources\FormControlResource;
use App\Models\FormControlPublicInvitation;
use App\Models\Lote;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;

trait HasPublicFormLinkAction
{
    public function sharePublicFormAction(): Action
    {
        return $this->publicFormLinkAction('sharePublicForm', 'Generar enlace para Inquilino', fields: [
            Forms\Components\Radio::make('income_type')
                ->label('Tipo de formulario')
                ->options(FormControlPublicInvitation::tenantIncomeTypes())
                ->in(array_keys(FormControlPublicInvitation::tenantIncomeTypes()))
                ->descriptions([
                    FormControlPublicInvitation::TENANT => 'Para que el inquilino cargue sus datos, documentación y vehículos.',
                    FormControlPublicInvitation::TENANT_VISITOR => 'Para registrar a una persona que visitará al inquilino durante su estadía.',
                ])
                ->default(FormControlPublicInvitation::TENANT)
                ->required(),
            Forms\Components\Placeholder::make('tenant_only_notice')
                ->label('Importante')
                ->content('Esta opción es exclusiva para formularios de inquilinos y visitantes de inquilinos. Para trabajadores, proveedores u otros accesos, utilizá el formulario correspondiente.'),
        ])->modalDescription('Elegí el lote y para quién se generará el formulario.');
    }

    public function shareExtendedVisitFormAction(): Action
    {
        return $this->publicFormLinkAction(
            'shareExtendedVisitForm',
            'Generar enlace para Visita (+24hs)',
            FormControlPublicInvitation::EXTENDED_VISIT,
        )->modalDescription('Elegí el lote para el formulario de Visita (+24hs).');
    }

    private function publicFormLinkAction(string $name, string $label, ?string $fixedIncomeType = null, array $fields = []): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-share')
            ->color('info')
            ->visible(fn () => FormControlResource::canCreate() && auth()->user()->hasRole('owner') && auth()->user()->owner_id)
            ->modalHeading('Crear enlace para completar el formulario')
            ->modalSubmitActionLabel('Generar enlace')
            ->form([
                Forms\Components\Select::make('lote_id')
                    ->label('Lote')
                    ->options(fn () => auth()->user()->owner->lotes->mapWithKeys(fn (Lote $lote) => [$lote->id => $lote->getNombre()]))
                    ->default(fn () => auth()->user()->owner->lotes->count() === 1 ? auth()->user()->owner->lotes->first()->id : null)
                    ->required(),
                ...$fields,
            ])
            ->action(function (array $data, $livewire) use ($fixedIncomeType): void {
                $lote = auth()->user()->owner->lotes()->findOrFail($data['lote_id']);
                $incomeType = $fixedIncomeType ?? $data['income_type'];
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
