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
                ->modalHeading('¿Aprobar configuración?')
                ->modalDescription('Una vez que la configuración sea aprobada, no se podrán realizar más modificaciones. ¿Deseas continuar?')
                ->action(function ($record) {
                    // Validar que no exista otro aprobado en el mismo periodo
                    $periodo = $record->periodo ?? ($record->config['periodo'] ?? null);
                    if (!$periodo) {
                        throw ValidationException::withMessages([
                            'periodo' => 'No se pudo determinar el periodo de la configuración.'
                        ]);
                    }
                    $yaExiste = \App\Models\InvoiceConfig::where('status', 'Aprobado')
                        ->where('periodo', $periodo)
                        ->where('id', '!=', $record->id)
                        ->exists();
                    if ($yaExiste) {
                        throw ValidationException::withMessages([
                            'periodo' => 'Ya existe una configuración aprobada para este periodo. Solo puede haber una por periodo.'
                        ]);
                    }
                    $record->status = 'Aprobado';
                    $record->aprobe_user_id = auth()->id();
                    $record->aprobe_date = now();
                    $record->save();
                    \Filament\Notifications\Notification::make()
                        ->title('Configuración aprobada')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $facturasCount = \App\Models\Lote::where('is_facturable', true)->whereNotNull('owner_id')->count();
        $config = $data['config'] ?? [];
        // Eliminar cualquier bloque previo de 'other_properties'
        $config = array_values(array_filter($config, function($block) {
            return !(
                is_array($block)
                && isset($block['type'])
                && $block['type'] === 'other_properties'
            );
        }));
        // Agregar el bloque actualizado al final
        $config[] = [
            'type' => 'other_properties',
            'data' => [
                'facturas_count' => (string) $facturasCount,
            ],
        ];
        $data['config'] = $config;
        return $data;
    }
}
