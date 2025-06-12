<?php

namespace App\Filament\Resources\InvoiceConfigResource\Pages;

use App\Filament\Resources\InvoiceConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditInvoiceConfig extends EditRecord
{
    protected static string $resource = InvoiceConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Actions\Action::make('aprobar')
                ->label('Aprobar')
                ->icon('heroicon-m-check')
                ->color('success')
                ->visible(fn($record) => $record->status === 'Borrador')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->status = 'Aprobado';
                    $record->aprobe_user_id = auth()->id();
                    $record->aprobe_date = now();
                    $record->save();
                    \Filament\Notifications\Notification::make()
                        ->title('Configuración aprobada')
                        ->success()
                        ->send();
                    return redirect(request()->fullUrl());
                }),
        ];
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     // Validación de lotes excluidos vs lotes en grupos
    //     $config = $data['config'] ?? [];
    //     $bloqueGrupos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
    //     $grupos = $bloqueGrupos['data']['groups'] ?? [];
    //     $lotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->toArray();
    //     $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
    //     $lotesExcluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
    //     $enAmbos = array_intersect($lotesEnGrupos, $lotesExcluidos);
    //     if (count($enAmbos) > 0) {
    //         $nombres = \App\Models\Lote::whereIn('id', $enAmbos)->pluck('lote_id')->implode(', ');
    //         throw ValidationException::withMessages([
    //             'config' => 'No puedes excluir lotes que ya están asignados a un grupo: ' . $nombres
    //         ]);
    //     }
    //     return $data;
    // }
}
