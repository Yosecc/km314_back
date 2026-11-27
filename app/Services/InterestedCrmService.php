<?php

namespace App\Services;

use App\Models\Interested;
use App\Models\InterestedContact;
use App\Models\InterestedFollowUp;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class InterestedCrmService
{
    public function registerContact(Interested $interested, User $user, array $data): InterestedContact
    {
        return DB::transaction(function () use ($interested, $user, $data) {
            $contact = $interested->contacts()->create([
                'user_id' => $user->id,
                'channel' => $data['channel'],
                'direction' => $data['direction'] ?? 'outgoing',
                'result' => $data['result'] ?? null,
                'notes' => $data['notes'] ?? null,
                'contacted_at' => $data['contacted_at'] ?? now(),
            ]);

            if (! empty($data['interested_status_id'])) {
                $interested->interested_status_id = $data['interested_status_id'];
            }

            if (! empty($data['next_contact_at'])) {
                $this->scheduleFollowUp($interested, $user, [
                    'title' => $data['follow_up_title'] ?? 'Próximo contacto',
                    'channel' => $data['follow_up_channel'] ?? $data['channel'],
                    'due_at' => $data['next_contact_at'],
                    'reminder_at' => $data['reminder_at'] ?? $data['next_contact_at'],
                    'assigned_user_id' => $data['assigned_user_id'] ?? $interested->assigned_user_id ?? $user->id,
                    'notes' => $data['follow_up_notes'] ?? null,
                ]);
            }

            $interested->last_contacted_at = $interested->contacts()->max('contacted_at');
            $interested->save();
            $this->syncNextContact($interested);

            return $contact;
        });
    }

    public function scheduleFollowUp(Interested $interested, User $user, array $data): InterestedFollowUp
    {
        $followUp = $interested->followUps()->create([
            'assigned_user_id' => $data['assigned_user_id'] ?? $interested->assigned_user_id ?? $user->id,
            'created_by_user_id' => $user->id,
            'title' => $data['title'],
            'channel' => $data['channel'] ?? null,
            'notes' => $data['notes'] ?? null,
            'due_at' => $data['due_at'],
            'reminder_at' => $data['reminder_at'] ?? $data['due_at'],
            'status' => InterestedFollowUp::PENDING,
        ]);

        if (! $interested->assigned_user_id && $followUp->assigned_user_id) {
            $interested->assigned_user_id = $followUp->assigned_user_id;
            $interested->save();
        }

        $this->syncNextContact($interested);

        return $followUp;
    }

    public function completeFollowUp(InterestedFollowUp $followUp, User $user, array $data): InterestedContact
    {
        return DB::transaction(function () use ($followUp, $user, $data) {
            $followUp->update([
                'status' => InterestedFollowUp::COMPLETED,
                'completed_at' => now(),
                'completed_by_user_id' => $user->id,
            ]);

            $contact = $this->registerContact(
                $followUp->interested,
                $user,
                Arr::add($data, 'channel', $followUp->channel ?? 'phone'),
            );
            $this->syncNextContact($followUp->interested);

            return $contact;
        });
    }

    public function rescheduleFollowUp(InterestedFollowUp $followUp, array $data): void
    {
        $followUp->update([
            'due_at' => $data['due_at'],
            'reminder_at' => $data['reminder_at'] ?? $data['due_at'],
            'assigned_user_id' => $data['assigned_user_id'] ?? $followUp->assigned_user_id,
            'notified_at' => null,
        ]);

        $this->syncNextContact($followUp->interested);
    }

    public function syncNextContact(Interested $interested): void
    {
        $interested->forceFill([
            'next_contact_at' => $interested->followUps()->pending()->min('due_at'),
        ])->save();
    }
}
