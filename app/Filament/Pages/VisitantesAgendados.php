<?php

namespace App\Filament\Pages;

use App\Models\FormControl;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Models\FormControlPeople;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
class VisitantesAgendados extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.visitantes-agendados';
    protected static ?string $navigationLabel = 'Visitantes Agendados';
    protected static ?string $slug = 'scheduled-visitors';
    protected static ?string $navigationGroup = 'Control de acceso';

    public function table(Table $table): Table
    {
        $query = FormControlPeople::query()->whereHas('formControl', function ($query) {
            $query->where('status', 'Authorized')
					->whereRaw('CONCAT(start_date_range, " ", COALESCE(start_time_range, "00:00:00")) >= ?', [Carbon::now()]);
        });

        return $table
            ->query($query)
            ->columns([
                //TextColumn::make('id'),
				TextColumn::make('formControl.id')->formatStateUsing(function ($state){
					return '#FORM_'.$state;
				}),
                TextColumn::make('first_name')->formatStateUsing(function ($record){

                    return "{$record->first_name} {$record->last_name}";
                  }),
               	TextColumn::make('formControl.start_date_range')
					->formatStateUsing(function ($record){

                      return Carbon::parse("{$record->formControl->start_date_range} {$record->formControl->start_time_range}")->toDayDateTimeString();
                    })
                    ->searchable()
                    ->sortable()
					->label(__('general.start_date_range')),

            ])
           ->actions([
                Action::make('Ver Formulario')->url(fn (FormControlPeople $record): string => route('filament.admin.resources.form-controls.view', $record->formControl ))

            ])
            ;
    }

}
