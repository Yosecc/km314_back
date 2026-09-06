<?php

namespace App\Http\Controllers;

use App\Filament\Resources\FormControlResource;
use App\Models\FilesRequired;
use App\Models\FormControl;
use App\Models\FormControlPublicInvitation;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicFormControlController extends Controller
{
    public function show(string $token)
    {
        $invitation = FormControlPublicInvitation::resolveToken($token);
        abort_unless($invitation, 404);
        return view('public-form-control', [
            'invitation'=>$invitation, 'token'=>$token,
            'personDocuments'=>$this->documentsFor('Inquilino'),
            'vehicleDocuments'=>$this->documentsFor('car'),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = FormControlPublicInvitation::resolveToken($token);
        abort_unless($invitation, 404);
        if (! $invitation->isAvailable()) return redirect()->route('form-control-public.show',$token);

        $rules = [
            'start_date'=>['required','date','after_or_equal:today'], 'start_time'=>['required','date_format:H:i'],
            'end_date'=>['required','date','after_or_equal:start_date'], 'end_time'=>['required','date_format:H:i'],
            'people'=>['required','array','min:1'], 'people.*.dni'=>['required','digits_between:6,12'],
            'people.*.first_name'=>['required','string','max:255'], 'people.*.last_name'=>['required','string','max:255'],
            'people.*.phone'=>['nullable','string','max:15'], 'people.*.is_responsable'=>['nullable','boolean'],
            'people.*.is_acompanante'=>['nullable','boolean'], 'people.*.is_menor'=>['nullable','boolean'],
            'people.*.documents.*.file'=>['nullable','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'people.*.documents.*.name'=>['nullable','string','max:255'],
            'people.*.documents.*.expires_at'=>['nullable','date'],
            'vehicles'=>['nullable','array'], 'vehicles.*.marca'=>['required_with:vehicles.*.patente','nullable','string','max:255'],
            'vehicles.*.modelo'=>['required_with:vehicles.*.patente','nullable','string','max:255'],
            'vehicles.*.patente'=>['required_with:vehicles.*.marca','nullable','string','max:20'],
            'vehicles.*.color'=>['required_with:vehicles.*.patente','nullable','string','max:100'],
            'vehicles.*.documents.*.file'=>['nullable','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'vehicles.*.documents.*.name'=>['nullable','string','max:255'],
            'vehicles.*.documents.*.expires_at'=>['nullable','date'],
            'extra_documents'=>['nullable','array'], 'extra_documents.*.description'=>['nullable','string','max:255'],
            'extra_documents.*.file'=>['nullable','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'pets'=>['nullable','array'], 'pets.*.type'=>['nullable','string','max:100'], 'pets.*.breed'=>['nullable','string','max:100'],
            'pets.*.name'=>['nullable','string','max:100'], 'pets.*.vaccinated'=>['nullable','boolean'],
            'observations'=>['nullable','string','max:2000'], 'accept_terms'=>['accepted'],
        ];
        foreach ($this->documentsFor('Inquilino') as $index=>$document) {
            if ($document['required']) $rules["people.*.documents.{$index}.file"] = ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'];
            if ($document['date_required']) $rules["people.*.documents.{$index}.expires_at"] = ['required','date'];
        }
        foreach ($this->documentsFor('car') as $index=>$document) {
            if ($document['required']) $rules["vehicles.*.documents.{$index}.file"] = ['required_with:vehicles.*.patente','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'];
            if ($document['date_required']) $rules["vehicles.*.documents.{$index}.expires_at"] = ['required_with:vehicles.*.patente','date'];
        }
        $validated = $request->validate($rules, [], ['people'=>'personas','accept_terms'=>'términos y condiciones']);

        if ($validated['start_date'] === $validated['end_date'] && $validated['end_time'] <= $validated['start_time']) {
            throw ValidationException::withMessages(['end_time'=>'La hora de finalización debe ser posterior a la hora de inicio.']);
        }

        $personDocuments = $this->documentsFor('Inquilino');
        $vehicleDocuments = $this->documentsFor('car');
        $form = DB::transaction(function () use ($validated, $token, $personDocuments, $vehicleDocuments) {
            $invitation = FormControlPublicInvitation::query()->where('token_hash',hash('sha256',$token))->lockForUpdate()->firstOrFail();
            if (! $invitation->isAvailable()) throw ValidationException::withMessages(['link'=>'Este enlace ya fue utilizado o venció.']);
            $lote = $invitation->lote;
            $form = FormControl::create([
                'owner_id'=>$invitation->owner_id, 'user_id'=>$invitation->created_by_user_id,
                'access_type'=>['lote'], 'lote_ids'=>[$lote->getNombre()], 'income_type'=>['Inquilino'],
                'status'=>'OwnerPending', 'start_date_range'=>$validated['start_date'], 'start_time_range'=>$validated['start_time'],
                'end_date_range'=>$validated['end_date'], 'end_time_range'=>$validated['end_time'],
                'date_unilimited'=>false, 'observations'=>$validated['observations'] ?? null,
            ]);
            $form->dateRanges()->create(['start_date_range'=>$validated['start_date'],'start_time_range'=>$validated['start_time'],'end_date_range'=>$validated['end_date'],'end_time_range'=>$validated['end_time'],'date_unilimited'=>false]);

            foreach ($validated['people'] as $personData) {
                $person = $form->peoples()->create([
                    'dni'=>$personData['dni'],'first_name'=>$personData['first_name'],'last_name'=>$personData['last_name'],
                    'phone'=>$personData['phone'] ?? null,'is_responsable'=>(bool)($personData['is_responsable']??false),
                    'is_acompanante'=>(bool)($personData['is_acompanante']??false),'is_menor'=>(bool)($personData['is_menor']??false),
                ]);
                foreach ($personData['documents'] ?? [] as $documentIndex=>$document) if (! empty($document['file'])) {
                    $person->files()->create(['name'=>$personDocuments[$documentIndex]['name'] ?? $document['name'] ?? 'Documento personal','file'=>$document['file']->store('form-controls/people','public'),'fecha_vencimiento'=>$document['expires_at']??null]);
                }
            }
            foreach ($validated['vehicles'] ?? [] as $vehicleData) {
                if (blank($vehicleData['patente'] ?? null)) continue;
                $auto = $form->autos()->create(['marca'=>$vehicleData['marca'],'modelo'=>$vehicleData['modelo'],'patente'=>strtoupper($vehicleData['patente']),'color'=>$vehicleData['color'],'user_id'=>$invitation->created_by_user_id,'model'=>'FormControl']);
                foreach ($vehicleData['documents'] ?? [] as $documentIndex=>$document) if (! empty($document['file'])) {
                    $auto->files()->create(['name'=>$vehicleDocuments[$documentIndex]['name'] ?? $document['name'] ?? 'Documento del vehículo','file'=>$document['file']->store('form-controls/vehicles','public'),'fecha_vencimiento'=>$document['expires_at']??null]);
                }
            }
            foreach ($validated['extra_documents'] ?? [] as $document) if (! empty($document['file'])) {
                $form->files()->create(['user_id'=>$invitation->created_by_user_id,'description'=>$document['description']??null,'file'=>$document['file']->store('form-controls/extra','public')]);
            }
            foreach ($validated['pets'] ?? [] as $pet) if (filled($pet['name']??null) || filled($pet['type']??null)) {
                $form->mascotas()->create(['tipo_mascota'=>$pet['type']??null,'raza'=>$pet['breed']??null,'nombre'=>$pet['name']??null,'is_vacunado'=>(bool)($pet['vaccinated']??false)]);
            }
            $invitation->update(['used_at'=>now(),'used_form_control_id'=>$form->id]);
            return $form;
        });

        $ownerUser = $form->owner?->user;
        if ($ownerUser) Notification::make()->title('Formulario completado por un invitado')
            ->body('Revise y apruebe el formulario #'.$form->id.' para enviarlo a administración.')
            ->actions([Action::make('review')->label('Revisar')->url(FormControlResource::getUrl('view',['record'=>$form]))])
            ->sendToDatabase($ownerUser);
        return redirect()->route('form-control-public.show',$token)->with('submitted',true);
    }

    private function documentsFor(string $type): array
    {
        return collect(FilesRequired::where('type',$type)->first()?->required ?? [])->map(fn ($item) => [
            'name'=>$item['document'],'required'=>(bool)($item['is_required']??false),'date_required'=>(bool)($item['date_is_required']??false),
        ])->values()->all();
    }
}
