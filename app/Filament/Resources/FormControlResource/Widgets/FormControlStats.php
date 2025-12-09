<?php

namespace App\Filament\Resources\FormControlResource\Widgets;

use App\Models\FormControl;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FormControlStats extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $numPending = FormControl::where('owner_id', Auth::user()->owner_id)
            ->where('status', 'Pending')
            ->count();

        // Contar empleados pendientes y rechazados del propietario
        $employeesPending = Employee::where(function($q) {
                $q->whereHas('owners', function($ownerQuery) {
                    $ownerQuery->where('owner_id', Auth::user()->owner_id);
                })->orWhere('owner_id', Auth::user()->owner_id);
            })
            ->where('status', 'pendiente')
            ->count();

        $employeesRechazados = Employee::where(function($q) {
                $q->whereHas('owners', function($ownerQuery) {
                    $ownerQuery->where('owner_id', Auth::user()->owner_id);
                })->orWhere('owner_id', Auth::user()->owner_id);
            })
            ->where('status', 'rechazado')
            ->count();

        $arr = [
            
            Stat::make('Formularios Pendientes de aprobación', $numPending)
                ->icon('heroicon-o-clock')
                ->description('Ver todos')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->url('form-controls')
                ->color('warning'),
        ];

        if($employeesPending > 0) {
            $arr[] = Stat::make('Empleados Pendientes de aprobación', $employeesPending)
                ->icon('heroicon-o-user-circle')
                ->description('Ver todos')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->url('employees')
                ->color('warning');
        }

        if($employeesRechazados > 0) {
            $arr[] = Stat::make('Empleados Rechazados', $employeesRechazados)
                ->icon('heroicon-o-x-circle')
                ->description('Ver todos')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->url('employees')
                ->color('danger');
        }

        


        return $arr;
    }
}
