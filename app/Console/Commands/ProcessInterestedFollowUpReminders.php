<?php

namespace App\Console\Commands;

use App\Filament\Pages\InterestedMonitor;
use App\Models\InterestedFollowUp;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProcessInterestedFollowUpReminders extends Command
{
    protected $signature = 'interesteds:process-reminders';

    protected $description = 'Envía recordatorios internos de seguimientos comerciales';

    public function handle(): int
    {
        InterestedFollowUp::query()
            ->with(['interested', 'assignedUser'])
            ->pending()
            ->whereNull('notified_at')
            ->whereNotNull('assigned_user_id')
            ->where(function (Builder $query) {
                $query->where(fn (Builder $q) => $q->whereNotNull('reminder_at')->where('reminder_at', '<=', now()))
                    ->orWhere(fn (Builder $q) => $q->whereNull('reminder_at')->where('due_at', '<=', now()));
            })
            ->chunkById(100, function ($followUps) {
                foreach ($followUps as $followUp) {
                    if (! $followUp->assignedUser || ! $followUp->interested) {
                        continue;
                    }

                    Notification::make()
                        ->title($followUp->isOverdue() ? 'Seguimiento comercial vencido' : 'Seguimiento comercial pendiente')
                        ->body($followUp->interested->full_name.' · '.$followUp->title.' · '.$followUp->due_at->format('d/m/Y H:i'))
                        ->warning()
                        ->actions([
                            Action::make('open')->label('Abrir monitor')->url(InterestedMonitor::getUrl(['timingFilter' => $followUp->isOverdue() ? 'overdue' : 'today'])),
                        ])
                        ->sendToDatabase($followUp->assignedUser);

                    $followUp->forceFill(['notified_at' => now()])->saveQuietly();
                }
            });

        return self::SUCCESS;
    }
}
