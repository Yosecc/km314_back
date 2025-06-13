<?php

namespace App\Filament\Resources\InvoiceConfigResource\Pages;

use App\Filament\Resources\InvoiceConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInvoiceConfig extends CreateRecord
{
    protected static string $resource = InvoiceConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $facturasCount = \App\Models\Lote::where('is_facturable', true)->whereNotNull('owner_id')->count();
        $config = $data['config'] ?? [];
        // Asegurarse de que $config es un array indexado
        if (!is_array($config)) {
            $config = [];
        }
        // Agregar el bloque 'other_properties' al final
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
