<?php

namespace App\Filament\Widgets;

use App\Models\Interested;
use App\Models\InterestedContact;
use App\Models\InterestedFollowUp;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class InterestedCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = InterestedFollowUp::class;

    protected static ?string $heading = 'Agenda de interesados';

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'locale' => 'es',
            'editable' => false,
            'selectable' => true,
            'headerToolbar' => [
                'left' => 'dayGridMonth,timeGridWeek,timeGridDay',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create', InterestedFollowUp::class))
                ->mountUsing(function (Form $form, array $arguments) {
                    $form->fill([
                        'due_at' => $arguments['start'] ?? now(),
                        'reminder_at' => $arguments['start'] ?? now(),
                        'assigned_user_id' => auth()->id(),
                        'status' => InterestedFollowUp::PENDING,
                    ]);
                })
                ->mutateFormDataUsing(fn (array $data) => $data + ['created_by_user_id' => auth()->id(), 'status' => InterestedFollowUp::PENDING]),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (InterestedFollowUp $record) => auth()->user()->can('update', $record))
                ->mutateFormDataUsing(function (array $data) {
                    $data['notified_at'] = null;

                    return $data;
                }),
            Actions\DeleteAction::make()->visible(fn (InterestedFollowUp $record) => auth()->user()->can('delete', $record)),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('interested_id')->label('Interesado')->options(
                Interested::query()->orderBy('first_name')->orderBy('last_name')->get()->mapWithKeys(fn (Interested $record) => [$record->id => $record->full_name])
            )->searchable()->required(),
            Forms\Components\TextInput::make('title')->label('Tarea')->default('Próximo contacto')->required()->maxLength(255),
            Forms\Components\Select::make('channel')->label('Canal')->options(InterestedContact::CHANNELS),
            Forms\Components\Select::make('assigned_user_id')->label('Responsable')->options(User::query()->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
            Forms\Components\DateTimePicker::make('due_at')->label('Fecha y hora')->seconds(false)->required(),
            Forms\Components\DateTimePicker::make('reminder_at')->label('Recordar desde')->seconds(false),
            Forms\Components\Textarea::make('notes')->label('Indicaciones')->rows(3)->columnSpanFull(),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        if (! auth()->user()?->can('view_any_interested::follow::up')) {
            return [];
        }

        return InterestedFollowUp::query()
            ->with(['interested.interestedLevel', 'assignedUser'])
            ->whereBetween('due_at', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (InterestedFollowUp $followUp) {
                $color = match (mb_strtolower($followUp->interested->interestedLevel?->name ?? '')) {
                    'alto' => '#279467', 'medio' => '#d59424', 'bajo' => '#7c8798', default => '#7047a1',
                };
                if ($followUp->status === InterestedFollowUp::COMPLETED) {
                    $color = '#64748b';
                }
                if ($followUp->isOverdue()) {
                    $color = '#d34750';
                }

                return [
                    'id' => $followUp->id,
                    'title' => $followUp->interested->full_name.' · '.$followUp->title,
                    'start' => $followUp->due_at->toIso8601String(),
                    'end' => $followUp->due_at->copy()->addMinutes(30)->toIso8601String(),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                ];
            })->all();
    }
}
