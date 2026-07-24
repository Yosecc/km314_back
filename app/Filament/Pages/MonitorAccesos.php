<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ActivitiesResource;
use App\Models\Activities;
use App\Models\ActivitiesPeople;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class MonitorAccesos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static string $view = 'filament.pages.monitor-accesos';

    protected static ?string $navigationLabel = 'Monitor de accesos';

    protected static ?string $title = 'Monitor de accesos';

    protected static ?string $slug = 'access-monitor';

    protected static ?string $navigationGroup = 'Control de acceso';

    protected static ?int $navigationSort = 2;

    public string $period = 'today';

    public string $movementType = 'all';

    public string $search = '';

    public static function canAccess(): bool
    {
        return ActivitiesResource::canViewAny();
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['today', '24h', '7d'], true)) {
            $this->period = $period;
            unset($this->monitorData);
        }
    }

    public function setMovementType(string $movementType): void
    {
        if (in_array($movementType, ['all', 'Entry', 'Exit', 'alerts'], true)) {
            $this->movementType = $movementType;
            unset($this->monitorData);
        }
    }

    public function updatedSearch(): void
    {
        unset($this->monitorData);
    }

    public function forceExit(string $model, int $modelId): void
    {
        $latestMovement = ActivitiesPeople::query()
            ->select('activities_people.*')
            ->join('activities as latest_activity', 'latest_activity.id', '=', 'activities_people.activities_id')
            ->whereNull('activities_people.deleted_at')
            ->where('activities_people.model', $model)
            ->where('activities_people.model_id', $modelId)
            ->with('activitie')
            ->orderByDesc('latest_activity.created_at')
            ->orderByDesc('activities_people.id')
            ->first();

        if (! $latestMovement || $latestMovement->activitie?->type !== 'Entry') {
            Notification::make()
                ->title('La persona ya no figura adentro')
                ->warning()
                ->send();

            unset($this->monitorData);

            return;
        }

        $tipoEntrada = match ($model) {
            'Owner', 'OwnerFamily', 'OwnerSpontaneousVisit' => 1,
            'Employee' => 2,
            'FormControl', 'FormControlPeople' => 3,
            default => 0,
        };

        if ($tipoEntrada === 0) {
            Notification::make()
                ->title('No se pudo determinar el tipo de persona')
                ->danger()
                ->send();

            return;
        }

        $userName = auth()->user()?->name ?? 'Sistema';
        $activity = Activities::create([
            'lote_ids' => $latestMovement->activitie->lote_ids,
            'form_control_id' => $latestMovement->activitie->form_control_id,
            'tipo_entrada' => $tipoEntrada,
            'type' => 'Exit',
            'observations' => 'Salida forzada desde Monitor de accesos por: '.$userName,
        ]);

        ActivitiesPeople::create([
            'activities_id' => $activity->id,
            'model' => $model,
            'model_id' => $modelId,
            'type' => null,
        ]);

        unset($this->monitorData);

        Notification::make()
            ->title('Salida registrada')
            ->body('La persona fue retirada del listado de personas adentro.')
            ->success()
            ->send();
    }

    #[Computed]
    public function monitorData(): array
    {
        $now = now();
        $start = match ($this->period) {
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            default => $now->copy()->startOfDay(),
        };

        // Se trae un margen anterior para poder comparar el primer movimiento visible
        // con el movimiento que lo precedió.
        $bufferStart = $start->copy()->subDays(7);

        $rows = ActivitiesPeople::query()
            ->select('activities_people.*')
            ->join('activities as timeline_activity', 'timeline_activity.id', '=', 'activities_people.activities_id')
            ->whereNull('activities_people.deleted_at')
            ->where('timeline_activity.created_at', '>=', $bufferStart)
            ->with($this->peopleRelations())
            ->orderByDesc('timeline_activity.created_at')
            ->orderByDesc('activities_people.id')
            ->limit(1200)
            ->get();

        $events = $this->buildTimeline($rows, $start);
        $inside = $this->currentPeopleInside();
        $insideIdentities = $inside->pluck('identity')->flip();

        $events = $events->map(function (array $event) use ($insideIdentities) {
            $event['can_force_exit'] = $event['movement'] === 'Entry'
                && blank($event['resolved_time'])
                && $insideIdentities->has($event['identity']);

            return $event;
        });

        $events = $this->applySearch($events);
        $inside = $this->applySearch($inside);

        $visibleEvents = $events
            ->when(
                in_array($this->movementType, ['Entry', 'Exit'], true),
                fn (Collection $items) => $items->where('movement', $this->movementType)
            )
            ->when(
                $this->movementType === 'alerts',
                fn (Collection $items) => $items->whereNotNull('alert')
            )
            ->take(180)
            ->values();

        $alerts = $events
            ->filter(fn (array $event) => filled($event['alert']) && blank($event['resolved_time']) && $event['can_force_exit'])
            ->map(fn (array $event) => [
                'key' => 'event-'.$event['id'],
                'title' => $event['alert'],
                'detail' => $event['name'],
                'time' => $event['time'],
                'severity' => 'error',
                'can_force_exit' => true,
                'model' => $event['model'],
                'model_id' => $event['model_id'],
            ]);

        $longStays = $inside
            ->filter(fn (array $person) => $person['minutes_inside'] >= 720)
            ->map(fn (array $person) => [
                'key' => 'stay-'.$person['identity'],
                'title' => 'Permanencia prolongada',
                'detail' => $person['name'].' lleva '.$person['duration'].' adentro',
                'time' => $person['time'],
                'severity' => 'warning',
                'can_force_exit' => true,
                'model' => $person['model'],
                'model_id' => $person['model_id'],
            ]);

        $allAlerts = $alerts
            ->concat($longStays)
            ->unique('key')
            ->take(12)
            ->values();

        return [
            'events' => $visibleEvents,
            'inside' => $inside->take(80)->values(),
            'alerts' => $allAlerts,
            'stats' => [
                'inside' => $inside->count(),
                'entries' => $events->where('movement', 'Entry')->count(),
                'exits' => $events->where('movement', 'Exit')->count(),
                'alerts' => $allAlerts->count(),
            ],
            'period_label' => match ($this->period) {
                '24h' => 'últimas 24 horas',
                '7d' => 'últimos 7 días',
                default => 'hoy',
            },
            'updated_at' => $now->format('H:i:s'),
        ];
    }

    protected function buildTimeline(EloquentCollection $rows, Carbon $start): Collection
    {
        $previousMovement = [];
        $events = collect();

        foreach ($rows->sortBy(fn (ActivitiesPeople $row) => sprintf(
            '%s-%012d',
            $row->activitie?->created_at?->format('YmdHis.u') ?? '0',
            $row->id
        )) as $row) {
            if (! $row->activitie) {
                continue;
            }

            $event = $this->mapPersonEvent($row);
            $identity = $event['identity'];
            $previous = $previousMovement[$identity] ?? null;

            if ($previous === $event['movement']) {
                $event['alert'] = $event['movement'] === 'Entry'
                    ? 'Doble entrada sin salida intermedia'
                    : 'Doble salida sin entrada intermedia';
            }

            $previousMovement[$identity] = $event['movement'];

            if ($event['occurred_at']->gte($start)) {
                $events->push($event);
            }
        }

        $pendingDoubleEntries = [];

        foreach ($events as $key => $event) {
            if ($event['alert'] === 'Doble entrada sin salida intermedia') {
                $pendingDoubleEntries[$event['identity']][] = $key;
            }

            if ($event['movement'] !== 'Exit' || empty($pendingDoubleEntries[$event['identity']])) {
                continue;
            }

            foreach ($pendingDoubleEntries[$event['identity']] as $entryKey) {
                $incident = $events->get($entryKey);
                $incident['resolved_time'] = $event['time'];
                $incident['resolved_message'] = $event['is_forced_exit']
                    ? 'Incidencia resuelta con salida forzada a las '.$event['time']
                    : 'Incidencia resuelta con salida a las '.$event['time'];
                $events->put($entryKey, $incident);
            }

            unset($pendingDoubleEntries[$event['identity']]);
        }

        return $events
            ->sortByDesc(fn (array $event) => sprintf(
                '%s-%012d',
                $event['occurred_at']->format('YmdHis.u'),
                $event['id']
            ))
            ->values();
    }

    protected function currentPeopleInside(): Collection
    {
        $latestTimes = DB::table('activities_people as latest_people')
            ->join('activities as latest_activity', 'latest_activity.id', '=', 'latest_people.activities_id')
            ->whereNull('latest_people.deleted_at')
            ->groupBy('latest_people.model', 'latest_people.model_id')
            ->select([
                'latest_people.model',
                'latest_people.model_id',
                DB::raw('MAX(latest_activity.created_at) as latest_at'),
            ]);

        $latestRows = ActivitiesPeople::query()
            ->select('activities_people.*')
            ->join('activities as current_activity', 'current_activity.id', '=', 'activities_people.activities_id')
            ->joinSub($latestTimes, 'latest', function ($join) {
                $join
                    ->on('latest.model', '=', 'activities_people.model')
                    ->on('latest.model_id', '=', 'activities_people.model_id')
                    ->on('latest.latest_at', '=', 'current_activity.created_at');
            })
            ->whereNull('activities_people.deleted_at')
            ->with($this->peopleRelations())
            ->orderByDesc('current_activity.created_at')
            ->orderByDesc('activities_people.id')
            ->limit(800)
            ->get()
            ->unique(fn (ActivitiesPeople $row) => $this->identityFor($row));

        return $latestRows
            ->filter(fn (ActivitiesPeople $row) => $row->activitie?->type === 'Entry')
            ->map(function (ActivitiesPeople $row) {
                $event = $this->mapPersonEvent($row);
                $minutes = (int) $event['occurred_at']->diffInMinutes(now());

                return $event + [
                    'minutes_inside' => $minutes,
                    'duration' => $this->formatDuration($minutes),
                ];
            })
            ->sortByDesc('minutes_inside')
            ->values();
    }

    protected function mapPersonEvent(ActivitiesPeople $row): array
    {
        $activity = $row->activitie;
        $person = $row->getPeople();
        $firstName = trim((string) ($person?->first_name ?? ''));
        $lastName = trim((string) ($person?->last_name ?? ''));
        $name = trim($firstName.' '.$lastName);
        $name = $name !== '' ? $name : 'Persona sin datos';
        $occurredAt = Carbon::parse($activity->created_at);
        $rawModel = (string) $row->getRawOriginal('model');
        $initials = Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return [
            'id' => $row->id,
            'identity' => $this->identityFor($row),
            'model' => $rawModel,
            'model_id' => (int) $row->model_id,
            'activity_id' => $activity->id,
            'movement' => $activity->type,
            'name' => $name,
            'initials' => $initials ?: '?',
            'dni' => $person?->dni ?: 'Sin DNI',
            'category' => match ($rawModel) {
                'Owner' => 'Propietario',
                'OwnerFamily' => 'Familiar',
                'Employee' => 'Empleado',
                'OwnerSpontaneousVisit' => 'Visita espontánea',
                'FormControl', 'FormControlPeople' => $this->formControlCategory($person),
                default => 'Persona',
            },
            'lot' => $this->formatLot($activity, $person),
            'observations' => trim((string) $activity->observations),
            'occurred_at' => $occurredAt,
            'date' => $occurredAt->isToday() ? 'Hoy' : $occurredAt->format('d/m/Y'),
            'time' => $occurredAt->format('H:i'),
            'alert' => null,
            'resolved_time' => null,
            'resolved_message' => null,
            'is_forced_exit' => Str::contains(
                $this->normalizeSearch($activity->observations),
                'salida forzada'
            ),
            'url' => ActivitiesResource::getUrl('view', ['record' => $activity->id]),
            'search_text' => $this->normalizeSearch(implode(' ', [
                $name,
                $person?->dni,
                $this->formatLot($activity, $person),
                $rawModel,
            ])),
        ];
    }

    protected function formControlCategory($person): string
    {
        $incomeTypes = collect($person?->formControl?->income_type ?? [])
            ->filter()
            ->implode(', ');

        return $incomeTypes !== '' ? $incomeTypes : 'Visitante';
    }

    protected function formatLot($activity, $person): string
    {
        $value = $activity->lote_ids;

        if (blank($value) && $person?->formControl) {
            $value = $person->formControl->lote_ids;
        }

        if (is_array($value)) {
            return collect($value)->flatten()->filter()->implode(', ') ?: 'Sin lote';
        }

        if (blank($value)) {
            return 'Sin lote';
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->flatten()->filter()->implode(', ') ?: 'Sin lote';
        }

        return trim((string) $value, " \t\n\r\0\x0B[]\"");
    }

    protected function applySearch(Collection $items): Collection
    {
        $search = $this->normalizeSearch($this->search);

        if ($search === '') {
            return $items->values();
        }

        return $items
            ->filter(fn (array $item) => str_contains($item['search_text'], $search))
            ->values();
    }

    protected function identityFor(ActivitiesPeople $row): string
    {
        return $row->getRawOriginal('model').'-'.$row->model_id;
    }

    protected function normalizeSearch(?string $value): string
    {
        return Str::lower(Str::ascii(trim((string) $value)));
    }

    protected function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return $hours.' h '.($minutes % 60).' min';
        }

        $days = intdiv($hours, 24);

        return $days.' d '.($hours % 24).' h';
    }

    protected function peopleRelations(): array
    {
        return [
            'activitie.formControl',
            'owner',
            'ownerFamily.familiarPrincipal',
            'employee',
            'formControlPeople.formControl',
            'ownerSpontaneousVisit.owner',
        ];
    }
}
