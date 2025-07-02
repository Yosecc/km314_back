<?php

namespace App\Filament\Resources\FormIncidentResponseResource\Pages;

use App\Filament\Resources\FormIncidentResponseResource;
use App\Models\FormIncidentQuestion;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFormIncidentResponse extends CreateRecord
{
    protected static string $resource = FormIncidentResponseResource::class;

    protected function getCreatedNotificationRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Si viene un form_incident_type_id en la URL, pre-cargar las preguntas
        $formTypeId = request()->query('form_incident_type_id');

        if ($formTypeId) {
            $questions = FormIncidentQuestion::whereHas('types', function($q) use ($formTypeId) {
                $q->where('form_incident_type_id', $formTypeId);
            })->orderBy('order')->get(['id', 'question', 'type', 'options', 'required']);

            $data['form_incident_type_id'] = $formTypeId;
            $data['questions_structure'] = $questions->toArray();
            $data['answers'] = $questions->map(function($q) {
                return [
                    'question_id' => $q->id,
                    'answer' => null,
                ];
            })->toArray();
        }

        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        // Si hay un form_incident_type_id en la URL, cargar las preguntas después del montaje
        $formTypeId = request()->query('form_incident_type_id');
        if ($formTypeId) {
            $questions = FormIncidentQuestion::whereHas('types', function($q) use ($formTypeId) {
                $q->where('form_incident_type_id', $formTypeId);
            })->orderBy('order')->get(['id', 'question', 'type', 'options', 'required']);

            $this->form->fill([
                'form_incident_type_id' => $formTypeId,
                'questions_structure' => $questions->toArray(),
                'answers' => $questions->map(function($q) {
                    return [
                        'question_id' => $q->id,
                        'answer' => null,
                    ];
                })->toArray(),
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Agregar campos obligatorios que no están llegando
        $data['user_id'] = auth()->id();
        $data['date'] = now()->toDateString();
        $data['time'] = now()->format('H:i');

        // Remover campos que no van a la base de datos
        unset($data['questions_structure']);

        // Asegurar que answers es un array válido
        if (!isset($data['answers']) || !is_array($data['answers'])) {
            $data['answers'] = [];
        }

        return $data;
    }
}
