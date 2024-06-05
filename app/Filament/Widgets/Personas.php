<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Activities;
use App\Models\ActivitiesPeople;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class Personas extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -10;

    protected static ?string $heading = 'Personas en el barrio (contadores)';
    

    // protected static ?string $pollingInterval = '30s';
    protected function getStats(): array
    {
        return [
            Stat::make('Propietarios', $this->getEntradasPropietarios()),
            Stat::make('Empleados', $this->getEntradasEmployee()),
            Stat::make('Visitantes', $this->getEntradasFormVisitantesGenerales())->description('Entrada general - Club playa - Club House'),
            Stat::make('Inquilinos', $this->getEntradasFormInquilinosLotes())->description('Lotes'),
            Stat::make('Trabajadores', $this->getEntradasFormTrabajadoresLotes())->description('Lotes'),
            Stat::make('Visitas', $this->getEntradasFormVisitaLotes())->description('Lotes')
            ,

        ];
    }

    public function getEntradasPropietarios()
    {
        $peopleInside = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->groupBy('model_id')
            ->where('model','Owner')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->distinct('model_id')
            ->count('model_id');
            // dd($peopleInside);
        return $peopleInside;
    }
    
    public function getEntradasEmployee()
    {
        $peopleInside = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->groupBy('model_id')
            ->where('model','Employee')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->distinct('model_id')
            ->count('model_id');
            // dd($peopleInside);
        return $peopleInside;
    }

    public function getEntradasFormVisitantesGenerales()
    {
        $validAccessTypes = ['general', 'playa', 'house'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($validAccessTypes) {
                foreach ($validAccessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            ->count();
    
        return $peopleInside;
    }

    public function getEntradasFormInquilinosLotes()
    {
        $accessTypes = ['lote'];
        $incomeTypes = ['Inquilino'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($accessTypes) {
                foreach ($accessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->where(function($query) use ($incomeTypes) {
                foreach ($incomeTypes as $type) {
                    $query->orWhere('form_controls.income_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            ->count();

        return $peopleInside;
    }

    public function getEntradasFormTrabajadoresLotes()
    {
        $accessTypes = ['lote'];
        $incomeTypes = ['Trabajador'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($accessTypes) {
                foreach ($accessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->where(function($query) use ($incomeTypes) {
                foreach ($incomeTypes as $type) {
                    $query->orWhere('form_controls.income_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            ->count();

        return $peopleInside;
    }

    public function getEntradasFormVisitaLotes()
    {
        $accessTypes = ['lote'];
        $incomeTypes = ['Visita'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($accessTypes) {
                foreach ($accessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->where(function($query) use ($incomeTypes) {
                foreach ($incomeTypes as $type) {
                    $query->orWhere('form_controls.income_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            ->count();

        return $peopleInside;
    }
}
