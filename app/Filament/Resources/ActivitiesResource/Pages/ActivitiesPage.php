<?php

namespace App\Filament\Resources\ActivitiesResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use App\Models\ActivitiesAuto;
use App\Models\ActivitiesPeople;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Resources\ActivitiesResource;

class ActivitiesPage extends CreateRecord
{
    protected static string $resource = ActivitiesResource::class;
 
    protected function getHeaderWidgets(): array
    {
        return [
            
            // \App\Filament\Resources\ActivitiesResource\Widgets\ActivitiesWidget::class,
            // \App\Filament\Resources\ActivitiesResource\Widgets\ActivitiesOptionsForm::class,
        ];
    }

   
    protected function afterCreate(): void
    {
        // dd('SI',$this->record, $this->data);

        $model = '';
        if($this->data['tipo_entrada'] == 1){
            $model = 'Owner';
        }else if ($this->data['tipo_entrada'] == 2){
            $model = 'Employee';
        }else if($this->data['tipo_entrada'] == 3){
            $model = 'FormControl';
        }

        $record = $this->record;
        $people = collect($this->data['peoples'])
                    ->map(function($people) use ($model, $record ){
                        return [
                            'activities_id' => $record->id ,
                            'model' => $model,
                            'model_id' => $people,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    });

        ActivitiesPeople::insert($people->toArray());

        $autos = collect($this->data['autos'])
                    ->map(function($auto) use ($model, $record ){
                        return [
                            'activities_id' => $record->id ,
                            'auto_id' => $auto,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    });

        ActivitiesAuto::insert($autos->toArray());
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    // //    dd('SI', $data);
    
    //     return $data;
    // }
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('edit')
    //             ->url(''),
    //         Action::make('delete')
    //             ->requiresConfirmation()
    //             // ->action(fn () => $this->post->delete()),
    //     ];
    // }
}
