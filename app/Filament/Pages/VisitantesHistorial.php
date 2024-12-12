<?php

namespace App\Filament\Pages;

use App\Models\FormControlPeople;
use App\Models\ActivitiesPeople;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class VisitantesHistorial extends Page implements HasForms, HasTable
{

    use HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.visitantes-historial';
    protected static ?string $navigationLabel = 'Historial de Visitantes';
    protected static ?string $slug = 'history-visitors';
    protected static ?string $navigationGroup = 'Control de acceso';

   public function table(Table $table): Table
   {
    $query = ActivitiesPeople::query()->select('activities_people.model_id')
        ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
        // ->where('activities_people.model', 'Owner')
        ->groupBy('activities_people.model_id')
        ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
        ;

       return $table
           ->query($query)
           ->columns([
               TextColumn::make('id'),
            //    TextColumn::make('formControl.id')->formatStateUsing(function ($state){
            //        return '#FORM_'.$state;
            //    }),
            //    TextColumn::make('first_name')->formatStateUsing(function ($record){

            //        return "{$record->first_name} {$record->last_name}";
            //      }),
            //       TextColumn::make('formControl.start_date_range')
            //        ->formatStateUsing(function ($record){

            //          return Carbon::parse("{$record->formControl->start_date_range} {$record->formControl->start_time_range}")->toDayDateTimeString();
            //        })
            //        ->searchable()
            //        ->sortable()
            //        ->label(__('general.start_date_range')),

           ])
        //   ->actions([
        //        Action::make('Ver Formulario')->url(fn (FormControlPeople $record): string => route('filament.admin.resources.form-controls.view', $record->formControl ))

        //    ])
           ;
   }


}
