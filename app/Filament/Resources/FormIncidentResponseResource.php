<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormIncidentResponseResource\Pages;
use App\Filament\Resources\FormIncidentResponseResource\RelationManagers;
use App\Models\FormIncidentResponse;
use Filament\Forms;
use Filament\Forms\Components\Builder as BuilderJSON;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormIncidentResponseResource extends Resource
{
    protected static ?string $model = FormIncidentResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('form_incident_type_id')
                    ->label('Tipo de formulario')
                    ->relationship('type', 'name')
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                Forms\Components\TimePicker::make('time')
                    ->label('Hora')
                    ->nullable()
                    ->default(now()->format('H:i')),

                Forms\Components\Repeater::make('answers')
                    ->label('Respuestas')
                    ->schema(function (callable $get) {
                        $typeId = $get('form_incident_type_id');
                        if (!$typeId) return [];
                        $questions = \App\Models\FormIncidentQuestion::whereHas('types', function($q) use ($typeId) {
                            $q->where('form_incident_type_id', $typeId);
                        })->orderBy('order')->get();
                        return $questions->map(function($question) {
                            $options = $question->options;
                            if (is_string($options) && !empty($options)) {
                                $options = json_decode($options, true) ?? [];
                            } elseif (!is_array($options)) {
                                $options = [];
                            }
                            $input = match ($question->type) {
                                'si_no' => Forms\Components\Select::make('answer')->options(['si' => 'Sí', 'no' => 'No'])->required(),
                                'abierta' => Forms\Components\TextInput::make('answer')->required(),
                                'seleccion_unica' => Forms\Components\Select::make('answer')->options($options)->required(),
                                'seleccion_multiple' => Forms\Components\CheckboxList::make('answer')->options($options)->required(),
                                default => Forms\Components\TextInput::make('answer'),
                            };
                            return Forms\Components\Fieldset::make('Pregunta: ' . strip_tags($question->question))
                                ->schema([
                                    Forms\Components\Hidden::make('question_id')->default($question->id),
                                    $input,
                                ]);
                        })->toArray();
                    })
                    ->columnSpanFull()
                    ->reactive(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('form_incident_type_id')->label('Tipo de formulario'),
                Tables\Columns\TextColumn::make('user_id')->label('Usuario'),
                Tables\Columns\TextColumn::make('date')->label('Fecha')->date(),
                Tables\Columns\TextColumn::make('time')->label('Hora')->time(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormIncidentResponses::route('/'),
            'create' => Pages\CreateFormIncidentResponse::route('/create'),
            'edit' => Pages\EditFormIncidentResponse::route('/{record}/edit'),
        ];
    }
}
