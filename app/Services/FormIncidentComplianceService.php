<?php

namespace App\Services;

use App\Models\FormIncidentUserRequirement;
use App\Models\FormIncidentResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FormIncidentComplianceService
{
    /**
     * Obtiene el estado de cumplimiento para un usuario específico
     */
    public function getComplianceStatusForUser(User $user): array
    {
        $requirements = FormIncidentUserRequirement::active()
            ->forUser($user->id)
            ->with('formIncidentType')
            ->get();

        $status = [
            'pending' => [],
            'overdue' => [],
            'completed' => [],
            'is_fully_compliant' => true,
        ];

        foreach ($requirements as $requirement) {
            $complianceData = $this->checkRequirementCompliance($user, $requirement);

            if ($complianceData['status'] === 'completed') {
                $status['completed'][] = $complianceData;
            } elseif ($complianceData['status'] === 'overdue') {
                $status['overdue'][] = $complianceData;
                $status['is_fully_compliant'] = false;
            } else {
                $status['pending'][] = $complianceData;
                $status['is_fully_compliant'] = false;
            }
        }

        return $status;
    }

    /**
     * Verifica el cumplimiento de un requerimiento específico
     */
    public function checkRequirementCompliance(User $user, FormIncidentUserRequirement $requirement): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        // Determinar el período a verificar según la frecuencia
        $periodStart = $this->getPeriodStart($requirement, $now);
        $periodEnd = $this->getPeriodEnd($requirement, $now);
        $deadline = $this->getDeadlineForPeriod($requirement, $now);

        // Verificar si existe una respuesta en el período
        $response = FormIncidentResponse::where('user_id', $user->id)
            ->where('form_incident_type_id', $requirement->form_incident_type_id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->latest()
            ->first();

        $status = 'pending';
        if ($response) {
            $status = 'completed';
        } elseif ($now->greaterThan($deadline)) {
            $status = 'overdue';
        }

        return [
            'requirement' => $requirement,
            'status' => $status,
            'deadline' => $deadline,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'response' => $response,
            'is_overdue' => $now->greaterThan($deadline),
            'hours_until_deadline' => $now->diffInHours($deadline, false),
        ];
    }

    /**
     * Obtiene el inicio del período según la frecuencia
     */
    private function getPeriodStart(FormIncidentUserRequirement $requirement, Carbon $date): Carbon
    {
        switch ($requirement->frequency) {
            case 'daily':
                return $date->copy()->startOfDay();
            case 'weekly':
                return $date->copy()->startOfWeek();
            case 'monthly':
                return $date->copy()->startOfMonth();
            default:
                return $date->copy()->startOfDay();
        }
    }

    /**
     * Obtiene el final del período según la frecuencia
     */
    private function getPeriodEnd(FormIncidentUserRequirement $requirement, Carbon $date): Carbon
    {
        switch ($requirement->frequency) {
            case 'daily':
                return $date->copy()->endOfDay();
            case 'weekly':
                return $date->copy()->endOfWeek();
            case 'monthly':
                return $date->copy()->endOfMonth();
            default:
                return $date->copy()->endOfDay();
        }
    }

    /**
     * Obtiene la fecha límite para completar el formulario
     */
    private function getDeadlineForPeriod(FormIncidentUserRequirement $requirement, Carbon $date): Carbon
    {
        $deadlineTime = Carbon::parse($requirement->deadline_time);

        switch ($requirement->frequency) {
            case 'daily':
                return $date->copy()
                    ->setTime($deadlineTime->hour, $deadlineTime->minute, 0);

            case 'weekly':
                // Para semanal, usar el último día válido de la semana
                $validDays = $requirement->days_of_week ?? [1, 2, 3, 4, 5]; // Por defecto lunes a viernes
                $lastValidDay = max($validDays);
                return $date->copy()
                    ->startOfWeek()
                    ->addDays($lastValidDay - 1)
                    ->setTime($deadlineTime->hour, $deadlineTime->minute, 0);

            case 'monthly':
                return $date->copy()
                    ->endOfMonth()
                    ->setTime($deadlineTime->hour, $deadlineTime->minute, 0);

            default:
                return $date->copy()
                    ->setTime($deadlineTime->hour, $deadlineTime->minute, 0);
        }
    }

    /**
     * Obtiene todos los usuarios con formularios vencidos
     */
    public function getUsersWithOverdueRequirements(): Collection
    {
        $users = User::whereHas('formIncidentRequirements', function ($query) {
            $query->active();
        })->get();

        return $users->filter(function ($user) {
            $status = $this->getComplianceStatusForUser($user);
            return count($status['overdue']) > 0;
        });
    }

    /**
     * Obtiene estadísticas generales de cumplimiento
     */
    public function getGeneralComplianceStats(): array
    {
        $totalUsers = User::whereHas('formIncidentRequirements')->count();
        $compliantUsers = 0;
        $overdueUsers = 0;

        if ($totalUsers > 0) {
            $users = User::whereHas('formIncidentRequirements', function ($query) {
                $query->active();
            })->get();

            foreach ($users as $user) {
                $status = $this->getComplianceStatusForUser($user);
                if ($status['is_fully_compliant']) {
                    $compliantUsers++;
                } elseif (count($status['overdue']) > 0) {
                    $overdueUsers++;
                }
            }
        }

        return [
            'total_users' => $totalUsers,
            'compliant_users' => $compliantUsers,
            'overdue_users' => $overdueUsers,
            'pending_users' => $totalUsers - $compliantUsers - $overdueUsers,
            'compliance_percentage' => $totalUsers > 0 ? round(($compliantUsers / $totalUsers) * 100, 2) : 0,
        ];
    }
}
