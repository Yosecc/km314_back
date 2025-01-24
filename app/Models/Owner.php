<?php

namespace App\Models;

use App\Mail\createUserEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Owner extends Model
{
    use HasFactory;
    protected $fillable = ['cuit','number','piso','dto','first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'birthdate', 'gender', 'profile_picture','dni','user_id','owner_status_id'];

    protected $with = ['autos','lotes','status'];

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','Owner');
    }

    public function trabajadores()
    {
        return $this->hasMany(Employee::class,'owner_id');
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','Owner')->latest();
    }

    public function user(){
        return $this->hasOne(User::class,'owner_id');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    public function families()
    {
        return $this->hasMany(OwnerFamily::class);
    }

    public function status()
    {
        return $this->belongsTo(OwnerStatus::class,'owner_status_id');
    }

    public function nombres(): string{
        return $this->first_name." ".$this->last_name;
    }

    public function createUser(){
        try {

            $user = User::where('email',$this->email)->first();

            if(!$user){
                $user = new User();
            }

            $user->name = $this->first_name." ".$this->last_name;
            $user->email = $this->email;
            $user->password = bcrypt($this->dni);
            $user->owner_id = $this->id;
            $user->save();

            Owner::where('id',$this->id)->update(['user_id' => $user->id]);

            Mail::to('yosec.cervino@gmail.com')->send(new createUserEmail( $user, $this->dni ));

            return [
                'status' => 'success',
                'message' => 'Usuario creado correctamente'
            ];

        } catch (\Throwable $th) {
            return [
                'status' => 'error',
                'message' => $th->getMessage()
            ];
        }

    }
}
