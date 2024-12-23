<?php

namespace App\Filament\Resources;


use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
                    ->minDate(fn ($context) => $context == 'create' ? now()->format('Y-m-d')."00:00:00" : null)
                    ->default(now()->format('Y-m-d H:m:s'))
                    ->live()
                    ->required()
                    ->disabled(function($context){
                        return $context == 'edit' ? true:false;
                    }),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la incidencia')
                    ->required()
                    ->live()
                    ->maxLength(255)
                    ->disabled(function($context, Get $get){
                        return $context == 'edit' && ($get('user_id') != Auth::user()->id) ? true:false;
                    }),

                Forms\Components\TextInput::make('description')
                    ->label('Descripción de la incidencia')
                    ->required()
                    ->columnSpanFull()
                    ->disabled(function($context, Get $get){
                        return $context == 'edit' && ($get('user_id') != Auth::user()->id) ? true:false;
                    }),

                Repeater::make('notes')
                    ->label('Notas')
                    ->relationship()
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => $state['user_name'] ?? null)
                    ->deletable(fn( $context): ?bool => $context != 'edit' ? true : false)
                    ->schema([

                        Forms\Components\TextInput::make('description')
                            ->label('Descripción de la nota')
                            ->required()
                            ->columnSpanFull()
                            ->disabled(function($context, Get $get){
                                return $context == 'edit' && ($get('user_id') != Auth::user()->id) ? true:false;
                            }),

                        FileUpload::make('file')
                            ->disabled(function($context, Get $get){
                                return $context == 'edit' && ($get('user_id') != Auth::user()->id) ? true:false;
                            }),
                            Actions::make([
                                Action::make('open_file')
                                    ->label('Abrir archivo')
                                    ->icon('heroicon-m-eye')
                                    ->url(function ($record, $context) {
                                        return Storage::url($record->file);
                                     })
                                    ->openUrlInNewTab()
                                    ->disabled(function($context, Get $get){
                                        return $context == 'edit' && ($get('user_id') != Auth::user()->id) ? true:false;
                                    })
                            ])
                            ->visible(function($record){
                                return $record ? true : false;
                            }),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::user()->id)
                            ->required()
                            ->visible(true),

                        Forms\Components\TextInput::make('user_name')
                            ->label('Nombre de usuario')
                            ->formatStateUsing(fn (Get $get): string => ( $get('user_id') ? User::find($get('user_id'))->name : 'Usuario no identificado' ) )
                            ->readOnly()
                            ->visible(true),

                    ])
                    ->columns(1)
                    ->columnSpanFull()
                ,
                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),

                Forms\Components\TextInput::make('user_name')
                    ->label('Nombre de usuario')
                    ->formatStateUsing(fn (Get $get): string => $get('user_id') ? User::find($get('user_id'))->name : '' )
                    ->readOnly(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_incident')
                    ->label('Fecha')
                    ->searchable(),
                // Tables\Columns\IconColumn::make('status')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
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
            'create' => Pages\CreateIncident::route('/create'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),

        ];
    }
}
