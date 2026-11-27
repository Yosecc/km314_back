<?php

namespace App\Filament\Resources\InterestedResource\RelationManagers;

use App\Models\InterestedContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Historial de contactos';

    protected static ?string $modelLabel = 'contacto';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('user_id')->default(auth()->id()),
            Forms\Components\DateTimePicker::make('contacted_at')->label('Fecha y hora')->default(now())->seconds(false)->required(),
            Forms\Components\Select::make('channel')->label('Canal')->options(InterestedContact::CHANNELS)->required(),
            Forms\Components\Select::make('direction')->label('Dirección')->options(InterestedContact::DIRECTIONS)->default('outgoing')->required(),
            Forms\Components\TextInput::make('result')->label('Resultado')->maxLength(100),
            Forms\Components\Textarea::make('notes')->label('Resumen')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('contacted_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('contacted_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('channel')->label('Canal')->badge()->formatStateUsing(fn ($state) => InterestedContact::CHANNELS[$state] ?? $state),
            Tables\Columns\TextColumn::make('result')->label('Resultado')->placeholder('-')->searchable(),
            Tables\Columns\TextColumn::make('notes')->label('Resumen')->limit(70)->placeholder('-'),
            Tables\Columns\TextColumn::make('user.name')->label('Registrado por')->placeholder('-'),
        ])->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}
