<?php

namespace App\Filament\Resources\FormIncidentResponseResource\Pages;

use App\Filament\Resources\FormIncidentResponseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewFormIncidentResponse extends ViewRecord
{
    protected static string $resource = FormIncidentResponseResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;
        $answers = $record->answers ?? [];
        $questions = \App\Models\FormIncidentQuestion::whereHas('types', function($q) use ($record) {
            $q->where('form_incident_type_id', $record->form_incident_type_id);
        })->get(['id', 'question', 'type', 'options']);
        $questionsMap = $questions->keyBy('id');

        $entries = [
            Section::make('Datos generales')
                ->schema([
                    TextEntry::make('type.name')->label('Tipo de formulario'),
                    TextEntry::make('user.name')->label('Usuario'),
                    TextEntry::make('date')->label('Fecha'),
                    TextEntry::make('time')->label('Hora'),
                ]),
            Section::make('Respuestas')
                ->schema(
                    collect($answers)->map(function($ans) use ($questionsMap) {
                        $q = $questionsMap[$ans['question_id']] ?? null;
                        $label = $q?->question ?? 'Pregunta';
                        $type = $q?->type ?? null;
                        $options = $q?->options;
                        if (is_string($options) && !empty($options)) {
                            $options = json_decode($options, true) ?? [];
                        } elseif (!is_array($options)) {
                            $options = [];
                        }
                        $value = $ans['answer'];
                        if ($type === 'si_no') {
                            $value = $value === 'si' ? 'Sí' : ($value === 'no' ? 'No' : $value);
                        } elseif ($type === 'seleccion_unica') {
                            if (array_is_list($options)) {
                                $value = isset($options[(int)$ans['answer']]) ? $options[(int)$ans['answer']] : $ans['answer'];
                            } else {
                                foreach ($options as $key => $optLabel) {
                                    if ((string)$key === (string)$ans['answer']) {
                                        $value = $optLabel;
                                        break;
                                    }
                                }
                            }
                        } elseif ($type === 'seleccion_multiple' && is_array($ans['answer'])) {
                            $labels = collect($ans['answer'])->map(fn($val) => $options[$val] ?? $val)->toArray();
                            $value = implode(', ', $labels);
                        }
                        return TextEntry::make('q_' . $ans['question_id'])
                            ->label($label)
                            ->default($value);
                    })->toArray()
                ),
        ];
        return $infolist->schema($entries);
    }
}
