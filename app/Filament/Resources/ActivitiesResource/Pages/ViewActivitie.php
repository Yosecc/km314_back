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
    
        
        $data['peoples'] = $this->record->peoples->pluck('model_id')->toArray();
        $data['autos'] = $this->record->autos->pluck('auto_id')->toArray();
        // dd($data, $this->record, $this->record->autos);
    
       return $data;
    }
}
