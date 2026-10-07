<?php

namespace App\Filament\Pages;

use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Services\CurrentPeopleInsideQuery;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    public function table(Table $table): Table
    {
        return $table
            ->query($this->peopleInsideQuery())
            ->columns([
                Tables\Columns\TextColumn::make('model_id')
                    ->label('Formulario / tipo')
                    ->formatStateUsing(fn ($state, ActivitiesPeople $record) => match ($record->getRawOriginal('model')) {
                        'FormControl' => $record->formControlPeople?->form_control_id ?? '-',
                        'Owner' => 'Propietario',
                        'Employee' => 'Empleado',
                        'OwnerFamily' => 'Familiar',
                        'OwnerSpontaneousVisit' => 'Visita',
                        default => $record->getRawOriginal('model') ?: '-',
                    }),
                Tables\Columns\TextColumn::make('dni')->label('DNI')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $this->personFor($record)?->dni ?? '-')
                    ->searchable(query: fn (Builder $query, string $search) => $this->searchPeople($query, 'dni', $search)),
                Tables\Columns\TextColumn::make('first_name')->label('Nombre')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $this->personFor($record)?->first_name ?? '-')
                    ->searchable(query: fn (Builder $query, string $search) => $this->searchPeople($query, 'first_name', $search)),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $this->personFor($record)?->last_name ?? '-')
                    ->searchable(query: fn (Builder $query, string $search) => $this->searchPeople($query, 'last_name', $search)),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $this->personTypeFor($record)),
                Tables\Columns\TextColumn::make('lote')->label('Lote')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $this->lotFor($record))
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function (Builder $lotQuery) use ($search) {
                            $lotQuery->where('current_activity.lote_ids', 'like', "%{$search}%")
                                ->orWhere(function (Builder $formQuery) use ($search) {
                                    $formQuery->where('activities_people.model', 'FormControl')
                                        ->whereHas('formControlPeople.formControl', fn (Builder $controlQuery) => $controlQuery->where('lote_ids', 'like', "%{$search}%"));
                                });
                        });
                    }),
                Tables\Columns\TextColumn::make('ultima_entrada')->label('Última entrada')
                    ->getStateUsing(fn (ActivitiesPeople $record) => $record->activitie?->created_at)
                    ->dateTime()
                    ->searchable(query: function (Builder $query, string $search) {
                        try {
                            $query->whereDate('current_activity.created_at', Carbon::parse($search)->toDateString());
                        } catch (\Throwable) {
                            $query->whereRaw('1 = 0');
                        }
                    }),
            ])
            ->actions([
                Action::make('ver_actividad')
                    ->label('Ver actividad')
                    ->url(fn (ActivitiesPeople $record) => route('filament.admin.resources.activities.view', $record->activities_id))
                    ->icon('heroicon-o-eye')
                    ->openUrlInNewTab(),
                Action::make('forzar_salida')
                    ->label('Forzar salida')
                    ->action(fn (ActivitiesPeople $record) => $this->forceExit($record))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ])
            ->bulkActions([
                BulkAction::make('forzar_salida_bulk')
                    ->label('Forzar salida')
                    ->action(fn ($records) => $records->each(fn (ActivitiesPeople $record) => $this->forceExit($record)))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ]);
    }

    private function peopleInsideQuery(): Builder
    {
        return CurrentPeopleInsideQuery::make()
            ->with([
                'activitie.formControl',
                'owner',
                'ownerFamily.familiarPrincipal',
                'employee',
                'formControlPeople.formControl',
                'ownerSpontaneousVisit.owner',
            ])
            ->orderByDesc('current_activity.created_at')
            ->orderByDesc('activities_people.id');
    }

    private function personFor(ActivitiesPeople $record)
    {
        return $record->getPeople();
    }

    private function searchPeople(Builder $query, string $field, string $search): void
    {
        $query->where(function (Builder $peopleQuery) use ($field, $search) {
            $peopleQuery
                ->orWhere(fn (Builder $q) => $q->where('activities_people.model', 'Owner')->whereHas('owner', fn (Builder $person) => $person->where($field, 'like', "%{$search}%")))
                ->orWhere(fn (Builder $q) => $q->where('activities_people.model', 'Employee')->whereHas('employee', fn (Builder $person) => $person->where($field, 'like', "%{$search}%")))
                ->orWhere(fn (Builder $q) => $q->where('activities_people.model', 'OwnerFamily')->whereHas('ownerFamily', fn (Builder $person) => $person->where($field, 'like', "%{$search}%")))
                ->orWhere(fn (Builder $q) => $q->where('activities_people.model', 'OwnerSpontaneousVisit')->whereHas('ownerSpontaneousVisit', fn (Builder $person) => $person->where($field, 'like', "%{$search}%")))
                ->orWhere(fn (Builder $q) => $q->where('activities_people.model', 'FormControl')->whereHas('formControlPeople', fn (Builder $person) => $person->where($field, 'like', "%{$search}%")));
        });
    }

    private function personTypeFor(ActivitiesPeople $record): string
    {
        return match ($record->getRawOriginal('model')) {
            'Owner' => 'Propietario',
            'Employee' => 'Empleado',
            'OwnerFamily' => 'Familiar',
            'OwnerSpontaneousVisit' => 'Visita espontánea',
            'FormControl' => collect($record->formControlPeople?->formControl?->income_type ?? [])->filter()->implode(', ') ?: 'Visitante',
            default => 'Persona',
        };
    }

    private function lotFor(ActivitiesPeople $record): string
    {
        $lot = $record->activitie?->lote_ids ?: $record->formControlPeople?->formControl?->lote_ids;

        return is_array($lot) ? implode(', ', $lot) : trim((string) $lot, "[]\" ");
    }

    private function forceExit(ActivitiesPeople $record): void
    {
        $model = $record->getRawOriginal('model');
        $tipoEntrada = match ($model) {
            'Owner', 'OwnerFamily', 'OwnerSpontaneousVisit' => 1,
            'Employee' => 2,
            'FormControl' => 3,
            default => 0,
        };

        if ($tipoEntrada === 0) {
            return;
        }

        $activity = Activities::create([
            'lote_ids' => $this->lotFor($record),
            'form_control_id' => $record->activitie?->form_control_id,
            'tipo_entrada' => $tipoEntrada,
            'type' => 'Exit',
            'observations' => 'Salida forzada por: ' . (Auth::user()->name ?? 'Sistema'),
        ]);

        ActivitiesPeople::create([
            'activities_id' => $activity->id,
            'model' => $model,
            'model_id' => $record->model_id,
            'type' => null,
        ]);
    }
}
