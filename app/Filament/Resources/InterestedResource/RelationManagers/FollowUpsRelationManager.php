<?php

namespace App\Filament\Resources\InterestedResource\RelationManagers;

use App\Models\InterestedContact;
use App\Models\InterestedFollowUp;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'Seguimientos y recordatorios';

    protected static ?string $modelLabel = 'seguimiento';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('created_by_user_id')->default(auth()->id()),
            Forms\Components\Hidden::make('status')->default(InterestedFollowUp::PENDING),
            Forms\Components\TextInput::make('title')->label('Tarea')->default('Próximo contacto')->required()->maxLength(255),
            Forms\Components\Select::make('channel')->label('Canal')->options(InterestedContact::CHANNELS),
            Forms\Components\Select::make('assigned_user_id')->label('Responsable')->options(User::query()->orderBy('name')->pluck('name', 'id'))->default(auth()->id())->searchable()->required(),
            Forms\Components\DateTimePicker::make('due_at')->label('Fecha y hora')->seconds(false)->required(),
            Forms\Components\DateTimePicker::make('reminder_at')->label('Recordar desde')->seconds(false),
            Forms\Components\Textarea::make('notes')->label('Indicaciones')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('due_at')->columns([
            Tables\Columns\TextColumn::make('due_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable()->color(fn (InterestedFollowUp $record) => $record->isOverdue() ? 'danger' : null),
            Tables\Columns\TextColumn::make('title')->label('Tarea')->searchable(),
            Tables\Columns\TextColumn::make('channel')->label('Canal')->badge()->formatStateUsing(fn ($state) => InterestedContact::CHANNELS[$state] ?? $state)->placeholder('-'),
            Tables\Columns\TextColumn::make('assignedUser.name')->label('Responsable')->placeholder('-'),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'pending' => 'Pendiente', 'completed' => 'Completado', 'cancelled' => 'Cancelado', default => $state
            })->color(fn ($state) => match ($state) {
                'completed' => 'success', 'cancelled' => 'gray', default => 'warning'
            }),
        ])->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}
