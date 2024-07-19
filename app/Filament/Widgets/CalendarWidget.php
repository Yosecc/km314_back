<?php

namespace App\Filament\Widgets;

use App\Models\Lote;
use App\Models\Owner;

use App\Models\Property;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Models\ServiceRequest;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\EventResource;
use Filament\Forms\Components\DateTimePicker;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    
    public Model | string | null $model = ServiceRequest::class;
 
    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'dayGridWeek,dayGridDay,dayGridMonth',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()->mountUsing(
                function (Form $form, array $arguments) {
                    $form->fill([
                        'starts_at' => $arguments['start'] ?? null,
                        'ends_at' => $arguments['end'] ?? null
                    ]);
                }
            ),
        ];
    }
 
    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
 
    protected function viewAction() : Action
    {
        return Actions\ViewAction::make();
    }
 
    public function getFormSchema(): array
    {
        return [
            TextInput::make('name'), 
            Grid::make()
                ->schema([
                    Select::make('service_request_status_id')
                        // ->label(__("general.LoteStatus"))
                        ->required()
                        ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name'),

                    Select::make('service_request_type_id')
                        // ->label(__("general.LoteStatus"))
                        ->required()
                        ->relationship(name: 'serviceRequestType', titleAttribute: 'name'),

                    Select::make('service_id')
                        // ->label(__("general.LoteStatus"))
                        ->required()
                        ->relationship(name: 'service', titleAttribute: 'name'),
                ])->columns(3),
            Grid::make()
                ->schema([
                    DateTimePicker::make('starts_at'),
                    DateTimePicker::make('ends_at'),
                ]),

            Grid::make()
                ->schema([
                    Select::make('owner_id')->label(__("general.Owner"))
                        
                        ->relationship(name: 'owner')
                        ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}"),

                    Select::make('lote_id')
                        ->label(__("general.Lotes"))
                        ->options(Lote::get()->map(function($lote){
                            $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                            return $lote;
                        })
                        ->pluck('lote_name', 'id')->toArray()),

                    Select::make('propertie_id')
                        ->label(__("general.Propertie"))
                        ->options(Property::get()->pluck('identificador', 'id')->toArray()),
                ])->columns(3),
        ];
    }
    
    public function fetchEvents(array $fetchInfo): array
    {
        // return ['id'=> 'a','title' => 'My event','start'=> '2024-07-18'];
        return ServiceRequest::query()
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn (ServiceRequest $event) => [
                    'title' => $event->name,
                    'id' => $event->id,
                    'start' => $event->starts_at,
                    'end' => $event->ends_at,
                    'backgroundColor' => $event->service->color,
                    'borderColor' => $event->service->color,
                    // 'url' => EventResource::getUrl(name: 'view', parameters: ['record' => $event]),
                    // 'shouldOpenUrlInNewTab' => true
                ]
            )
            ->all();
    }
}