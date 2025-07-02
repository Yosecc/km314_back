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

    protected static ?string $navigationLabel = 'Formulario de incidencias';
    protected static ?string $label = 'Formulario de incidencias';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function getPluralModelLabel(): string
    {
        return 'Formulario de incidencias';
    }

    public static function getModelLabel(): string
    {
        return 'Formulario de incidencias';
    }

    public static function form(Form $form): Form
    {
        $isEdit = request()->routeIs('filament.admin.resources.form-incident-responses.edit');
        return $form
            ->schema([
                Forms\Components\Select::make('form_incident_type_id')
                    ->label('Tipo de formulario')
                    ->placeholder('Seleccione el tipo de formulario')
                    ->helperText('Solo se muestran los formularios que tienes asignados como obligatorios.')
                    ->options(function () {
                        // Solo mostrar formularios que el usuario tiene asignados como obligatorios
                        return \App\Models\FormIncidentUserRequirement::active()
                            ->forUser(auth()->id())
                            ->with('formIncidentType')
                            ->get()
                            ->pluck('formIncidentType.name', 'form_incident_type_id');
                    })
                    ->default(function () {
                        // Pre-seleccionar el tipo de formulario si viene como parámetro en la URL
                        return request()->query('form_incident_type_id');
                    })
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
                Forms\Components\Hidden::make('questions_structure'),
                Forms\Components\Repeater::make('answers')
                    ->label('Lista de Preguntas')
                    ->helperText('Responda cada pregunta del formulario de incidente.')
                    ->reorderable(false)
                    ->itemLabel(function (array $state): ?string {
                        // Agregar etiqueta única para cada item del repeater
                        return isset($state['question_id']) ? 'Pregunta #' . $state['question_id'] : null;
                    })
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

                                            // Asegurar que tenemos un array asociativo para mapear
                                            if (array_is_list($options)) {
                                                $associativeOptions = [];
                                                foreach ($options as $index => $option) {
                                                    $associativeOptions[$index] = $option;
                                                }
                                                $options = $associativeOptions;
                                            }

                                            $labels = collect($answer)->map(function($val) use ($options) {
                                                return $options[$val] ?? $val;
                                            })->toArray();
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
                                    return new HtmlString($q['question'] ?? '');
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
                                ->columns(1)
                                ->bulkToggleable(false)
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
            ->query(function () {
                // Solo mostrar las respuestas del usuario autenticado
                return FormIncidentResponse::where('user_id', auth()->id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('type.name')->label('Tipo de formulario'),
                // Tables\Columns\TextColumn::make('user.name')->label('Usuario'), // Quitamos esto ya que solo ve las suyas
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
            ->emptyStateHeading('No tienes formularios de incidencias')
            ->emptyStateDescription('Completa un formulario obligatorio para comenzar.');
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
