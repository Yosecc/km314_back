<?php

namespace App\Services;

use App\Models\Activities;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Support\Collection;

class ActivityOwnerNotificationService
{
    public function __construct(
        protected ApplicationNotificationService $notifications,
    ) {}

    public function send(Activities $activity): void
    {
        $lotNames = $this->lotNames($activity->lote_ids);

        if ($lotNames->isEmpty()) {
            return;
        }

        $lotsByUser = Lote::query()
            ->with(['sector', 'owner.user'])
            ->whereNotNull('owner_id')
            ->get()
            ->filter(fn (Lote $lot): bool => $lotNames->contains($lot->getNombre()))
            ->filter(fn (Lote $lot): bool => $lot->owner?->user instanceof User)
            ->groupBy(fn (Lote $lot): int => $lot->owner->user->id);

        $personNames = $this->personNames($activity);
        $movement = $activity->type === 'Exit' ? 'salida' : 'entrada';
        $preposition = $activity->type === 'Exit' ? 'del' : 'al';

        $lotsByUser->each(function (Collection $lots) use ($activity, $personNames, $movement, $preposition): void {
            /** @var User $recipient */
            $recipient = $lots->first()->owner->user;
            $recipientLotNames = $lots->map(fn (Lote $lot): string => $lot->getNombre())->unique()->values();
            $lotLabel = $recipientLotNames->join(', ');
            $peopleLabel = $personNames->isNotEmpty()
                ? ' de '.$personNames->join(', ')
                : '';

            $this->notifications->send(
                $recipient,
                ucfirst($movement).' registrada en tu lote',
                "Se registró la {$movement}{$peopleLabel} {$preposition} lote {$lotLabel}.",
                [
                    'type' => 'access_activity',
                    'activity_id' => $activity->id,
                    'movement' => $activity->type,
                    'lots' => $lotLabel,
                ],
                icon: 'heroicon-o-home-modern',
            );
        });
    }

    /** @return Collection<int, string> */
    protected function lotNames(mixed $value): Collection
    {
        $values = is_array($value) ? $value : [$value];

        return collect($values)
            ->flatMap(fn ($item): array => preg_split('/\s*(?:,|\s+-\s+)\s*/', trim((string) $item)) ?: [])
            ->filter()
            ->unique()
            ->values();
    }

    /** @return Collection<int, string> */
    protected function personNames(Activities $activity): Collection
    {
        return $activity->peoples()
            ->get()
            ->map(function ($movement): ?string {
                $person = $movement->getPeople();

                if (! $person) {
                    return null;
                }

                return trim((string) ($person->first_name ?? $person->nombre ?? '').' '.(string) ($person->last_name ?? $person->apellido ?? ''));
            })
            ->filter()
            ->unique()
            ->values();
    }
}
