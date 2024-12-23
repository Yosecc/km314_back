<?php

namespace App\Filament\Pages;

use App\Models\ActivitiesPeople;
use App\Models\FormControlPeople;
use App\Models\Lote;
use App\Models\OwnerStatus;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Filament\Forms;
use Filament\Forms\Components\Grid;
class VisitantesHistorial extends Page implements HasForms, HasTable
{

    use HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.visitantes-historial';
    protected static ?string $navigationLabel = 'Personas en el barrio';
    protected static ?string $title = 'Personas en el barrio';
    protected static ?string $label = 'Personas en el barrio';
    protected static ?string $slug = 'history-visitors';
    protected static ?string $navigationGroup = 'Control de acceso';

    public $ownerStatus;
    public function __construct()
    {
        $this->ownerStatus = OwnerStatus::all();
    }

    public static function getPluralModelLabel(): string
    {
        return 'Personas en el barrio';
    }

    public function isMoroso($record)
    {
        if($record->owner_status_id){
            $estado = $this->ownerStatus->where('id',$record->owner_status_id)->first();
            if($estado->id == 2){
                return true;
            }
        }
        return false;
    }

    public function getQuery()
    {
        return ActivitiesPeople::query()
        ->with(['model', 'activitie.formControl']) // Carga las relaciones necesarias
        ->select(
            new Expression("GROUP_CONCAT(activities_people.id) AS ids"),
            new Expression("GROUP_CONCAT(activities.created_at) AS created_at"),
            new Expression("GROUP_CONCAT(activities.lote_ids) AS lote_ids"),
            new Expression("GROUP_CONCAT(owners.owner_status_id) AS owner_status_id"),
            'activities_people.model_id',
            'activities_people.model',
            new Expression("
                CASE
                    WHEN activities_people.model = 'FormControl' THEN COALESCE(CONCAT(form_control_people.first_name, ' ', form_control_people.last_name), '')
                    WHEN activities_people.model = 'Owner' THEN COALESCE(CONCAT(owners.first_name, ' ', owners.last_name), '')
                    WHEN activities_people.model = 'Employee' THEN COALESCE(CONCAT(employees.first_name, ' ', employees.last_name), '')
                    WHEN activities_people.model = 'OwnerFamily' THEN COALESCE(CONCAT(owner_families.first_name, ' ', owner_families.last_name), '')
                    WHEN activities_people.model = 'OwnerSpontaneousVisit' THEN COALESCE(CONCAT(owner_spontaneous_visits.first_name, ' ', owner_spontaneous_visits.last_name), '')
                    ELSE ''
                END AS model_name
            "),
            new Expression("
                SUM(CASE WHEN activities.type = 'Entry' THEN 1 ELSE 0 END) AS total_entries
            "),
            new Expression("
                SUM(CASE WHEN activities.type = 'Exit' THEN 1 ELSE 0 END) AS total_exits
            ")
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
                ->where('activities_people.model', '=', 'OwnerFamily');
        })
        ->leftJoin('owner_spontaneous_visits', function ($join) {
            $join->on('activities_people.model_id', '=', 'owner_spontaneous_visits.id')
                ->where('activities_people.model', '=', 'OwnerSpontaneousVisit');
        })
        ->whereNotNull('activities_people.model_id')
        ->groupBy(
            'activities_people.model_id',
            'activities_people.model',
            'form_control_people.first_name',
            'form_control_people.last_name',
            'owners.first_name',
            'owners.last_name',
            'employees.first_name',
            'employees.last_name',
            'owner_families.first_name',
            'owner_families.last_name',
            'owner_spontaneous_visits.first_name',
            'owner_spontaneous_visits.last_name'
        )
        ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
        ->orderBy('activities.created_at', 'DESC');
    }
    public function table(Table $table): Table
    {
        //   dd($query->get());
            return $table
                ->query($this->getQuery())
                ->columns([

                    TextColumn::make('created_at')
                        ->label('Fecha de entrada')
                        ->dateTime()
                        ->color( fn ($record) => $this->isMoroso($record) ? 'danger' : null)
                        ->tooltip(fn ($record) => $this->isMoroso($record) ? 'Moroso' : null)
                        ,

                    TextColumn::make('model')
                        ->label('Tipo')
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'FormControlPeople' => 'Otros',
                            'FormControl' => 'Otros',
                            'Owner' => 'Propietario',
                            'Employee' => 'Trabajador',
                            'OwnerFamily' => 'Familiar',
                            'OwnerSpontaneousVisit' => 'Visitante espontaneo'
                        })
                        ->sortable()
                        ->color( fn ($record) =>  $this->isMoroso($record) ? 'danger' : null)
                        ->tooltip(fn ($record) => $this->isMoroso($record) ? 'Moroso' : null),

                    TextColumn::make('model_name')
                        ->label('Nombre')
                        ->searchable()
                        ->sortable()
                        ->color( fn ($record) =>  $this->isMoroso($record) ? 'danger' : null)
                        ->tooltip(fn ($record) => $this->isMoroso($record) ? 'Moroso' : null),

                    TextColumn::make('lote_ids')->badge()->label('Lote'),

                ])
                ->filters([
                    SelectFilter::make('lote_ids')->label('Lote')->multiple()->options(function(){
                        return Lote::get()->map(function($lote){
                            $lote['lote_name'] = $lote->getNombre();
                            return $lote;
                        })->pluck('lote_name', 'lote_name')->toArray();
                    }),
                    Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Fecha de entrada - Desde'),
                        DatePicker::make('created_until')->label('Fecha de entrada - Hasta'),

                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                ])
                ->actions([
                    Action::make('Ver entrada')
                        // ->url(fn (ActivitiesPeople $record): string => route('filament.admin.resources.activities.view', $record->activitie))
                        ->openUrlInNewTab(),

                    Action::make('Ver persona')
                        ->fillForm(function( $record){
                            // dd($record);
                            // $record->ownerSpontaneousVisit

                            switch ($record->model) {
                                case 'Owner':
                                    # code...
                                    $data = $record->owner->toArray();
                                    break;
                                case 'OwnerSpontaneousVisit':
                                    # code...
                                    $data = $record->ownerSpontaneousVisit->toArray();
                                    break;
                                case 'FormControl':
                                    # code...
                                    $data = $record->formControlPeople->toArray();
                                    break;
                                case 'FormControlPeople':
                                    # code...
                                    $data = $record->formControlPeople->toArray();
                                    break;
                                case 'Employee':
                                    # code...
                                    $data = $record->employee->toArray();
                                    break;
                                case 'OwnerFamily':
                                    # code...
                                    $data = $record->ownerFamily->toArray();
                                    break;

                                default:
                                    $data = [];
                                    break;
                            }

                            // dd($data);
                            return $data;
                        })
                        ->form([
                            Grid::make([
                                'default' => 2,
                            ])
                            ->schema([

                                Forms\Components\TextInput::make('dni')
                                    ->label(__("general.DNI"))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->numeric(),

                                Forms\Components\TextInput::make('first_name')
                                    ->label(__("general.FirstName"))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('last_name')
                                    ->label(__("general.LastName"))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')->label(__("general.Phone")),

                                Forms\Components\TextInput::make('parentage')
                                    ->label(__("general.Parentesco"))
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email'),

                            ])

                        ])
                        ->slideOver()
                        ->disabledForm()
                        ->modalSubmitAction(false)
                        // ->action(function (array $data, $record): void {
                        //     dd($data, $record);
                        //     // $record->author()->associate($data['authorId']);
                        //     // $record->save();
                        // })


                ])
                ;
    }
    public function getTableRecordKey($record): string
    {
        return (string) $record->ids; // Asegúrate de que nunca sea null
    }


}


