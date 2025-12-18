<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormControlTypeIncomeResource\Pages;
use App\Filament\Resources\FormControlTypeIncomeResource\RelationManagers;
use App\Models\FormControlTypeIncome;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormControlTypeIncomeResource extends Resource
{
    protected static ?string $model = FormControlTypeIncome::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Tipo de ingreso';
    protected static ?string $label = 'tipo de ingreso';
    public static function getPluralModelLabel(): string
    {
        return 'tipos de ingresos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('color')->type('color'),
                Forms\Components\Toggle::make('status')->required(),
                // Forms\Components\TagsInput::make('files_required')->label('Documentos requeridos'),
                // Forms\Components\RichEditor::make('terminos')->label('Términos y condiciones')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\IconColumn::make('status')->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFormControlTypeIncomes::route('/'),
            'create' => Pages\CreateFormControlTypeIncome::route('/create'),
            'edit' => Pages\EditFormControlTypeIncome::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubtiposRelationManager::class,
        ];
    }
}
