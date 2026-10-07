<?php

namespace App\Console\Commands;

use App\Models\PackageReception;
use App\Services\PackageReceptionService;
use Illuminate\Console\Command;

class ProcessPackageReceptionAlerts extends Command
{
    protected $signature = 'package-receptions:process-alerts';
    protected $description = 'Notifica recepciones vencidas y paquetes demorados en recepción';

    public function handle(PackageReceptionService $service): int
    {
        PackageReception::query()->where('status', PackageReception::EXPECTED)->where('expected_until', '<', now())->whereNull('overdue_notified_at')->each(function ($record) use ($service) {
            $service->notifyOwner($record, 'El paquete todavía no llegó', $record->reference_code.' superó el horario previsto.');
            $record->update(['overdue_notified_at'=>now()]);
        });
        PackageReception::query()->where('status', PackageReception::RECEIVED)->where('received_at', '<=', now()->subHours(config('package-receptions.pickup_warning_hours')))->whereNull('pickup_reminder_sent_at')->each(function ($record) use ($service) {
            $service->notifyOwner($record, 'Paquete pendiente de retiro', $record->reference_code.' continúa en recepción.');
            $record->update(['pickup_reminder_sent_at'=>now()]);
        });
        PackageReception::query()->where('status', PackageReception::RECEIVED)->where('received_at', '<=', now()->subHours(config('package-receptions.pickup_critical_hours')))->whereNull('pickup_critical_sent_at')->each(function ($record) use ($service) {
            $service->notifyOwner($record, 'Paquete con retiro demorado', $record->reference_code.' lleva varios días en recepción.');
            $record->update(['pickup_critical_sent_at'=>now()]);
        });
        return self::SUCCESS;
    }
}
