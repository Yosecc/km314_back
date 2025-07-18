<?php

namespace App\Filament\Pages;

use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Models\FormControlPeople;
use App\Models\Lote;
use App\Models\OwnerStatus;
use App\Models\PersonaEnElBarrio;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Auth;

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
    public function table(Table $table): Table
    {
        return $table
            ->query(PersonaEnElBarrio::query())
            ->defaultGroup('lote')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido')->searchable(),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->searchable(),
                Tables\Columns\TextColumn::make('lote')
                    ->label('Lote')
                    ->searchable()
                    ,
                Tables\Columns\TextColumn::make('ultima_entrada')
                    ->label('ultima_entrada')
                    ->searchable()
                    ->summarize(Count::make()->label('Total personas')),

            ])
            ->actions([
                Action::make('forzar_salida')
                    ->label('Forzar Salida')
                    ->action(function ($record) {
                        $userName = Auth::user()->name ?? 'Sistema';
                        $tipoEntrada = match ($record->model) {
                            'Owner', 'OwnerFamily', 'OwnerSpontaneousVisit' => 1,
                            'Employee' => 2,
                            'FormControl' => 3,
                            default => 0,
                        };

                        $activity = Activities::create([
                            'lote_ids' => $record->lote,
                            'form_control_id' => null,
                            'tipo_entrada' => $tipoEntrada,
                            'type' => 'Exit',
                            'observations' => 'Salida forzada por: ' . $userName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        ActivitiesPeople::create([
                            'activities_id' => $activity->id,
                            'model' => $record->model,
                            'model_id' => $record->model_id,
                            'type' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ])
            ->bulkActions([
            BulkAction::make('forzar_salida_bulk')
                ->label('Forzar Salida')
                ->action(function ($records) {
                    $userName = Auth::user()->name ?? 'Sistema';
                    foreach ($records as $record) {
                        $tipoEntrada = match ($record->model) {
                            'Owner', 'OwnerFamily', 'OwnerSpontaneousVisit' => 1,
                            'Employee' => 2,
                            'FormControl' => 3,
                            default => 0,
                        };

                        $activity = Activities::create([
                            'lote_ids' => $record->lote,
                            'form_control_id' => null,
                            'tipo_entrada' => $tipoEntrada,
                            'type' => 'Exit',
                            'observations' => 'Salida forzada por: ' . $userName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        ActivitiesPeople::create([
                            'activities_id' => $activity->id,
                            'model' => $record->model,
                            'model_id' => $record->model_id,
                            'type' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ]);
    }



}


