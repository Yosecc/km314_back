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
use Illuminate\Support\HtmlString;

class FormIncidentResponseResource extends Resource
{
    protected static ?string $model = FormIncidentResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Respuestas de incidentes';
    protected static ?string $label = 'Respuesta de incidente';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function getPluralModelLabel(): string
    {
        return 'Respuestas de incidentes';
    }

    public static function getModelLabel(): string
    {
        return 'Respuesta de incidente';
    }

    public static function form(Form $form): Form
    {
        $isEdit = request()->routeIs('filament.admin.resources.form-incident-responses.edit');
        return $form
            ->schema([
                Forms\Components\Select::make('form_incident_type_id')
                    ->label('Tipo de formulario')
                    ->placeholder('Seleccione el tipo de formulario')
                    ->helperText('Seleccione el tipo de formulario de incidente para mostrar las preguntas correspondientes.')
                    ->relationship('type', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) use ($isEdit) {
                        if ($isEdit) return; // No actualizar preguntas en edición
                        $questions = \App\Models\FormIncidentQuestion::whereHas('types', function($q) use ($state) {
                            $q->where('form_incident_type_id', $state);
                        })->orderBy('order')->get(['id', 'question', 'type', 'options', 'required']);
                        $set('questions_structure', $questions->toArray());
                        $set('answers', collect($questions)->map(function($q) {
                            return [
                                'question_id' => $q['id'],
                                'answer' => null,
                            ];
                        })->toArray());
                    })
                    ->disabled($isEdit),
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->placeholder('Seleccione el usuario que responde')
                    ->helperText('Seleccione el usuario que está respondiendo el formulario de incidente.')
                    ->relationship('user', 'name')
                    ->required()
                    ->disabled($isEdit),
                Forms\Components\Hidden::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                Forms\Components\Hidden::make('time')
                    ->label('Hora')
                    ->required()
                    ->default(now()->format('H:i')),
                Forms\Components\Hidden::make('questions_structure'),
                Forms\Components\Repeater::make('answers')
                    ->label('Lista de Preguntas')
                    ->helperText('Responda cada pregunta del formulario de incidente.')
                    ->reorderable(false)
                    ->schema(function () use ($isEdit) {
                        if ($isEdit) {
                            // Solo mostrar como texto en edición
                            return [
                                Forms\Components\Hidden::make('question_id'),
                                Forms\Components\Placeholder::make('pregunta')
                                    ->label('Pregunta')

                                    ->content(function (callable $get) {
                                        $questions = $get('../../questions_structure') ?? [];
                                        $q = collect($questions)->firstWhere('id', $get('question_id'));
                                        return new HtmlString($q['question'] ?? '');
                                        // return $q['question'] ?? '';
                                    }),
                                Forms\Components\Placeholder::make('respuesta')
                                    ->label('Respuesta')
                                    ->content(function (callable $get) {
                                        $questions = $get('../../questions_structure') ?? [];
                                        $q = collect($questions)->firstWhere('id', $get('question_id'));
                                        $answer = $get('answer');
                                        if (($q['type'] ?? null) === 'seleccion_multiple' && is_array($answer)) {
                                            $options = $q['options'] ?? [];
                                            if (is_string($options) && !empty($options)) {
                                                $options = json_decode($options, true) ?? [];
                                            } elseif (!is_array($options)) {
                                                $options = [];
                                            }
                                            $labels = collect($answer)->map(fn($val) => $options[$val] ?? $val)->toArray();
                                            return implode(', ', $labels);
                                        }
                                        if (($q['type'] ?? null) === 'si_no') {
                                            return $answer === 'si' ? 'Sí' : ($answer === 'no' ? 'No' : $answer);
                                        }
                                        if (($q['type'] ?? null) === 'seleccion_unica') {
                                            $options = $q['options'] ?? [];
                                            if (is_string($options) && !empty($options)) {
                                                $options = json_decode($options, true) ?? [];
                                            } elseif (!is_array($options)) {
                                                $options = [];
                                            }
                                            if (array_is_list($options)) {
                                                return isset($options[(int)$answer]) ? $options[(int)$answer] : $answer;
                                            } else {
                                                foreach ($options as $key => $label) {
                                                    if ((string)$key === (string)$answer) {
                                                        return $label;
                                                    }
                                                }
                                                return $answer;
                                            }
                                        }
                                        return $answer;
                                    }),
                            ];
                        }
                        // Modo creación: inputs interactivos
                        return [
                            Forms\Components\Hidden::make('question_id'),
                            Forms\Components\Placeholder::make('pregunta')
                                ->label('Pregunta')
                                ->content(function (callable $get) {
                                    $questions = $get('../../questions_structure') ?? [];
                                    $q = collect($questions)->firstWhere('id', $get('question_id'));
                                    return $q['question'] ?? '';
                                }),
                            Forms\Components\TextInput::make('answer')
                                ->label('Respuesta')
                                ->placeholder('Ingrese su respuesta')
                                ->required(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['required'] ?? false))
                                ->visible(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['type'] ?? null) === 'abierta'),
                            Forms\Components\Radio::make('answer')
                                ->label('Respuesta')
                                ->options(['si' => 'Sí', 'no' => 'No'])
                                ->required(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['required'] ?? false))
                                ->visible(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['type'] ?? null) === 'si_no'),
                            Forms\Components\Select::make('answer')
                                ->label('Respuesta')
                                ->placeholder('Seleccione una opción')
                                ->options(function (callable $get) {
                                    $q = collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'));
                                    $options = $q['options'] ?? [];
                                    if (is_string($options) && !empty($options)) {
                                        $options = json_decode($options, true) ?? [];
                                    } elseif (!is_array($options)) {
                                        $options = [];
                                    }
                                    return $options;
                                })
                                ->required(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['required'] ?? false))
                                ->visible(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['type'] ?? null) === 'seleccion_unica'),
                            Forms\Components\CheckboxList::make('answer')
                                ->label('Respuesta')
                                ->options(function (callable $get) {
                                    $q = collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'));
                                    $options = $q['options'] ?? [];
                                    if (is_string($options) && !empty($options)) {
                                        $options = json_decode($options, true) ?? [];
                                    } elseif (!is_array($options)) {
                                        $options = [];
                                    }
                                    return $options;
                                })
                                ->required(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['required'] ?? false))
                                ->visible(fn (callable $get) => (collect($get('../../questions_structure'))->firstWhere('id', $get('question_id'))['type'] ?? null) === 'seleccion_multiple'),
                        ];
                    })
                    ->columnSpanFull()
                    ->createItemButtonLabel(false)
                    ->disableItemDeletion()
                    ->disableItemCreation()
                    ->reactive()
                    ->disabled($isEdit),
            ])
            ->statePath('data')
            ->inlineLabel(false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('form_incident_type_id')->label('Tipo de formulario'),
                Tables\Columns\TextColumn::make('user_id')->label('Usuario'),
                Tables\Columns\TextColumn::make('date')->label('Fecha')->date(),
                Tables\Columns\TextColumn::make('time')->label('Hora')->time(),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha de creación')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                // Tables\Actions\EditAction::make(), // Eliminada la acción de editar
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->emptyStateHeading('No hay respuestas de incidentes')
            ->emptyStateDescription('Cree una nueva respuesta para comenzar.');
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
            'view' => Pages\ViewFormIncidentResponse::route('/{record}'),
            // 'edit' => Pages\EditFormIncidentResponse::route('/{record}/edit'), // Eliminada la ruta de edición
        ];
    }
}
