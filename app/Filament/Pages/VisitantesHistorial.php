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
use Illuminate\Database\Query\Expression;
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
  $query = ActivitiesPeople::query()
    ->with(['model','activitie.formControl'])// Carga la relación 'model'
    ->select(
        'activities_people.model_id',
        'activities_people.model',
	  	'activities_people.activities_id',
        new Expression("COALESCE(CONCAT(form_control_people.first_name, ' ', form_control_people.last_name), '') AS model_name")
    )
    ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
    ->leftJoin('form_control_people', function ($join) {
        $join->on('activities_people.model_id', '=', 'form_control_people.id')
            ->where('activities_people.model', '=', 'FormControl');
    })
    ->leftJoin('owners', function ($join) {
        $join->on('activities_people.model_id', '=', 'owners.id')
            ->where('activities_people.model', '=', 'Owner');
    })
    ->leftJoin('employees', function ($join) {
        $join->on('activities_people.model_id', '=', 'employees.id')
            ->where('activities_people.model', '=', 'Employee');
    })
    ->leftJoin('owner_families', function ($join) {
        $join->on('activities_people.model_id', '=', 'owner_families.id')
            ->where('activities_people.model', '=', 'OwnerFamilie');
    })
    ->whereNotNull('activities_people.model_id')
    ->groupBy(
        'activities_people.model_id',
        'activities_people.model',
	  	'activities_people.activities_id',
        'form_control_people.first_name',
        'form_control_people.last_name'
    )
    ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)');


	  //dd($query->get());
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('model_id')->label('ID del Modelo')->sortable(),
                TextColumn::make('model_name')->label('Nombre')->sortable(),
				TextColumn::make('activitie.formControl.access_type')->badge(),
				TextColumn::make('activitie.formControl.lote_ids')->badge(),
				TextColumn::make('activitie.formControl.start_date_range')->date(),
				TextColumn::make('activitie.formControl.start_time_range'),
				TextColumn::make('activitie.created_at')->dateTime(),
            ]);
   }
 public function getTableRecordKey($record): string
{
    return (string) $record->model_id; // Asegúrate de que nunca sea null
}


}
