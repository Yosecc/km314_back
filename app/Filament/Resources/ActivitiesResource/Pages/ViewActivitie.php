<?php

namespace App\Filament\Resources\ActivitiesResource\Pages;

use App\Filament\Resources\ActivitiesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewActivitie extends ViewRecord
{
    protected static string $resource = ActivitiesResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['peoples'] = $this->record->peoples->whereIn('model',['Owner','Employee','FormControl'])->pluck('model_id')->toArray();
        $data['autos'] = $this->record->autos->pluck('auto_id')->toArray();
        $data['families'] = $this->record->peoples->whereIn('model',['OwnerFamily'])->pluck('model_id')->toArray();
        // dd( $data['families'],$data['peoples'] );
        return $data;
    }
}
