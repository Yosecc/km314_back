<?php
namespace App\Filament\Widgets;

use App\Models\ActivitiesPeople;
use App\Models\Employee;
use App\Models\OwnerFamily;
use App\Models\PersonaEnElBarrio;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Models\Activities;


class EnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -8;
    protected static ?string $heading = 'PERSONAS EN EL BARRIO';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
        ->heading(self::$heading)
        ->query(PersonaEnElBarrio::query())
        ->defaultGroup('lote')
        ->columns([
            Tables\Columns\TextColumn::make('first_name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->label('Tipo')->searchable(),
            Tables\Columns\TextColumn::make('lote')
                ->label('Lote')
                ->searchable()
                ->summarize(Count::make()->label('Total personas')),
            Tables\Columns\TextColumn::make('ultima_entrada')->label('ultima_entrada')->searchable(),

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
        ]);
    }
}

