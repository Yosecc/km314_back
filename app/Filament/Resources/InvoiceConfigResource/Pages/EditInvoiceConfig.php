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
            Actions\Action::make('ver_borrador')
                ->label('Ver borrador de factura')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->visible(fn($record) => true)
                ->form([
                    \Filament\Forms\Components\Select::make('lote_type_id')
                        ->label(__('general.LoteType'))
                        ->live()
                        ->options(function () {
                            $lotes = \App\Models\loteType::get();
                            return $lotes->mapWithKeys(function ($lote) {
                                return [
                                    $lote->id => $lote->name
                                ];
                            });
                        }),
                    \Filament\Forms\Components\Select::make('lotes_id')
                        ->label('Lote')
                        ->live()
                        ->options(function (\Filament\Forms\Get $get) {
                            $lotes = \App\Models\Lote::get()->where('is_facturable', true);
                            if ($get('lote_type_id')) {
                                $lotes = $lotes->where('lote_type_id', $get('lote_type_id'));
                            }
                            return $lotes->mapWithKeys(function ($lote) {
                                return [
                                    $lote->id => $lote->getNombre()
                                ];
                            });
                        })
                        ->required(),
                ])
                ->modalHeading('Ver borrador de factura')
                ->modalSubmitActionLabel('Ver borrador')
                ->action(function (array $data, $record) {
                    $loteId = $data['lotes_id'] ?? null;
                    if (empty($loteId)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Selecciona un lote')
                            ->danger()
                            ->send();
                        return null;
                    }
                    $previewKey = 'invoice_preview_' . uniqid();
                    session([$previewKey => [
                        'invoice_config_id' => $record->id,
                        'lotes_id' => [$loteId],
                    ]]);
                    $url = route('invoice.preview', ['key' => $previewKey]);
                    \Filament\Notifications\Notification::make()
                        ->title('Vista previa de factura')
                        ->body('Haz clic <a href="' . $url . '" target="_blank" style="color:#2563eb;text-decoration:underline;">aquí</a> para ver el borrador de la factura seleccionada en una nueva ventana.')
                        ->success()
                        ->send();
                    return null;
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
