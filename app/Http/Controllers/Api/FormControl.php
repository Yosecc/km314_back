<?php

namespace App\Http\Controllers\Api;

use App\Models\Auto;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FormControlPeople;
use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use App\Models\FormControl as FormControlDB;

class FormControl extends Controller
{
    public function index(Request $request)
    {
        $formControl = FormControlDB::where('owner_id', $request->user()->owner->id)->with(['peoples','autos'])->orderBy('created_at','desc')->get();

        $misLotes = Lote::where('owner_id', $request->user()->owner->id)->with(['sector','loteStatus','loteType'])->get();
        
        return response()->json([
            'misForms' => $formControl->map(function($form){
                $form->status = $form->statusComputed();
                return $form;
            })->where('status','Pending'),
            'historicoForms' => $formControl->map(function($form){
                $form->status = $form->statusComputed();
                return $form;
            })->where('status','!=','Pending'),
            'misLotes' => $misLotes
        ]);
    }

    public function store(Request $request)
    {
        // return $request->all();

        $validator = Validator::make($request->all(), [
            'lote_ids' => 'nullable',
            'access_type' => 'required|string',
            'income_type' => 'nullable|string',
            'start_date_range' => 'required|date',
            'start_time_range' => 'nullable|date_format:H:i:s',
            'end_date_range' => 'nullable|date',
            'end_time_range' => 'nullable|date_format:H:i:s',
            'date_unilimited' => 'nullable',
            'observations' => 'nullable|string',
            'peoples' => 'array',
            'peoples.*.dni' => 'required',
            'peoples.*.first_name' => 'required',
            'peoples.*.last_name' => 'required',
            'peoples.*.phone' => 'required',
            'peoples.*.is_responsable' => 'required',
            'peoples.*.is_acompanante' => 'required',
            'peoples.*.is_menor' => 'required',
            'autos' => 'array',
            'autos.*.marca' => 'required',
            'autos.*.modelo' => 'required',
            'autos.*.patente' => 'required',
            'autos.*.color' => 'required',
        ], [], [
            // Atributos personalizados
            'lote_ids' => 'ID de lote',
            'access_type' => 'tipo de acceso',
            'income_type' => 'tipo de ingreso',
            'start_date_range' => 'fecha de inicio',
            'start_time_range' => 'hora de inicio',
            'end_date_range' => 'fecha de fin',
            'end_time_range' => 'hora de fin',
            'date_unilimited' => 'fecha ilimitada',
            'observations' => 'observaciones',
            'peoples.*.dni' => 'DNI de la persona',
            'peoples.*.first_name' => 'nombre de la persona',
            'peoples.*.last_name' => 'apellido de la persona',
            'peoples.*.phone' => 'teléfono de la persona',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'is_moroso'         => 0,
            'lote_ids'          => $request->lote_ids,
            'access_type'       => $request->access_type,
            'income_type'       => $request->income_type,
            'start_date_range'  => $request->start_date_range,
            'start_time_range'  => $request->start_time_range,
            'end_date_range'    => $request->end_date_range,
            'date_unilimited'   => filter_var($request->date_unilimited, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'end_time_range'    => $request->end_time_range,
            // 'status'            => 'Pending',
            // 'user_id'           => $request->user()->id,
            // 'owner_id'          => $request->user()->id,
            'observations'      => $request->observations,
            // 'created_at'        => now(),
            'updated_at'        => now(),
        ];

        if($request->id){

            FormControlDB::where('id', $request->id)->update($data);
            $idForm = $request->id;
        }else{
            $data['user_id'] = $request->user()->id;
            $data['owner_id'] = $request->user()->owner->id;
            $data['created_at'] = now();
            $data['status'] = 'Pending';
            // Insertar el formulario principal
            $idForm = FormControlDB::insertGetId($data);
        }
    
    
        // Insertar los datos de las personas
        $peoplesData = collect($request->peoples)->map(function($people) use ($idForm){

            $data = [
                'form_control_id' => $idForm,
                'dni'             => $people['dni'],
                'first_name'      => $people['first_name'],
                'last_name'       => $people['last_name'],
                'phone'           => $people['phone'],
                'is_responsable'  => filter_var($people['is_responsable'], FILTER_VALIDATE_BOOLEAN),
                'is_acompanante'  => filter_var($people['is_acompanante'], FILTER_VALIDATE_BOOLEAN),
                'is_menor'        => filter_var($people['is_menor'], FILTER_VALIDATE_BOOLEAN),
                // 'created_at'      => now(),
                'updated_at'      => now(),
            ];

            if(!isset($people['id'])){
                // $data['updated_at'] = now();
                $data['created_at'] = now();
            }

            FormControlPeople::updateOrInsert( [ 'id' => isset($people['id']) ? $people['id'] : null ] , $data );

            return $people;
            // return [
            //     'form_control_id' => $idForm,
            //     'dni'             => $people['dni'],
            //     'first_name'      => $people['first_name'],
            //     'last_name'       => $people['last_name'],
            //     'phone'           => $people['phone'],
            //     'is_responsable'  => filter_var($people['is_responsable'], FILTER_VALIDATE_BOOLEAN),
            //     'is_acompanante'  => filter_var($people['is_acompanante'], FILTER_VALIDATE_BOOLEAN),
            //     'is_menor'        => filter_var($people['is_menor'], FILTER_VALIDATE_BOOLEAN),
            //     'created_at'      => now(),
            //     'updated_at'      => now(),
            // ];
        });

        // FormControlPeople::insert($peoplesData->toArray());
        
        // Insertar los datos de los autos
        $autosData = collect($request->autos)->map(function($auto) use ($idForm, $request){
            $data = [
                'marca'      => $auto['marca'],
                'patente'    => $auto['patente'],
                'modelo'     => $auto['modelo'],
                'color'      => $auto['color'],
                'user_id'    => $request->user()->id,
                'model'      => 'FormControl',
                'model_id'   => $idForm,
                'created_at' => now(),
                // 'updated_at' => now(),
            ];

            if(!isset($auto['id'])){
                // $data['updated_at'] = now();
                $data['created_at'] = now();
            }

            Auto::updateOrInsert( [ 'id' => isset($auto['id']) ? $auto['id'] : null ] , $data );

            return $auto;
        });
        
        // Auto::insert($autosData->toArray());
    
        $formControl = FormControlDB::where('id', $idForm)->with(['peoples','autos'])->first();

        if(!$request->id){

            $recipient = User::find(1);
            
            Notification::make()
                ->title('Nuevo formulario #FORM_'.$idForm)
                ->sendToDatabase($recipient);
        }

        return response()->json($formControl);

    }
}
