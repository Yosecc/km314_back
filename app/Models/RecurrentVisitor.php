<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurrentVisitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id', 'dni', 'first_name', 'last_name', 'phone', 'identity_document',
        'status', 'observations', 'user_id',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function autos()
    {
        return $this->hasMany(Auto::class, 'model_id')->where('model', 'RecurrentVisitor');
    }

    public function nombres(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function vencidosAutosFile(): ?array
    {
        $files = $this->autos->flatMap(fn (Auto $auto) => $auto->files)
            ->filter(fn (AutoFile $file) => $file->fecha_vencimiento && Carbon::parse($file->fecha_vencimiento)->isPast())
            ->pluck('name')
            ->values()
            ->all();

        return $files ?: null;
    }
}
