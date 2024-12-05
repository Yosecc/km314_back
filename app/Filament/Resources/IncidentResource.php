<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Get;
use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Incidencias';
    protected static ?string $label = 'incidiencia';
    // protected static ?string $navigationGroup = 'Configuración';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                DateTimePicker::make('date_incident')
                    ->label('Fecha del incidente')
                    ->displayFormat('d/m/Y')
                    // ->prefix('Starts')
                    // ->suffix('at midnight')
                    ->minDate(now())
                    ->maxDate(now())
                    ->default(now()->format('Y-m-d H:m:s'))
                    ->live()
                    // ->readonly()// context view / edit
                    ,

                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la incidencia')
                    // ->default(function( $get){
                    //     return $get('date_incident');
                    // })
                    ->required()
                    ->live()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                ->label('Descripción de la incidencia')
                    ->required()
                    ->columnSpanFull(),

                // Forms\Components\TextInput::make('status')->default(0)->required(),

                Repeater::make('notes')
                    ->label('Notas')
                    ->relationship()
                    ->defaultItems(0)
                    // ->deletable(false)
                    ->schema([


                        // TextEntry::make('user_id')->formatStateUsing(fn (string $state): string => Auth::user()->name ),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción de la nota')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('file'),
                        Forms\Components\TextInput::make('user_id')
                            ->default(Auth::user()->id)
                            ->readOnly()
                            ->required()
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ,

                Forms\Components\TextInput::make('user_id')
                    ->default(Auth::user()->id)
                    ->formatStateUsing(fn (string $state): string => Auth::user()->name )
                    ->readOnly()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_incident')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIncidents::route('/'),
        ];
    }
}
