<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InterestedResource;
use App\Models\Interested;
use App\Models\InterestedContact;
use App\Models\InterestedFollowUp;
use App\Models\InterestedLevel;
use App\Models\InterestedOrigin;
use App\Models\InterestedStatus;
use App\Models\InterestedType;
use App\Models\User;
use App\Services\InterestedCrmService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class InterestedMonitor extends Page
{
    use HasPageShield {
        canAccess as protected canAccessPage;
    }
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.interested-monitor';

    protected static ?string $navigationLabel = 'Monitor de interesados';

    protected static ?string $title = 'Monitor de interesados';

    protected static ?string $slug = 'interested-monitor';

    protected static ?string $navigationGroup = 'Interesados';

    protected static ?int $navigationSort = 1;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $levelFilter = '';

    #[Url]
    public string $originFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $assignedFilter = '';

    #[Url]
    public string $timingFilter = 'active';

    #[Url]
    public string $sort = 'priority';

    public static function canAccess(): bool
    {
        return static::canAccessPage() && (auth()->user()?->can('view_any_interested') ?? false);
    }

    public function updated(string $property): void
    {
        if (! in_array($property, ['search', 'statusFilter', 'levelFilter', 'originFilter', 'typeFilter', 'assignedFilter', 'timingFilter', 'sort'], true)) {
            return;
        }

        $this->resetPage();
        unset($this->monitorData, $this->filterOptions);
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'levelFilter', 'originFilter', 'typeFilter', 'assignedFilter');
        $this->timingFilter = 'active';
        $this->sort = 'priority';
        $this->resetPage();
        unset($this->monitorData);
    }

    public function registerContactAction(): Action
    {
        return Action::make('registerContact')
            ->label('Registrar contacto')->icon('heroicon-o-chat-bubble-left-right')->color('primary')->size('sm')
            ->record(fn (array $arguments) => Interested::query()->findOrFail($arguments['interested']))
            ->visible(fn () => auth()->user()->can('create', InterestedContact::class))
            ->modalHeading(fn (Interested $record) => 'Registrar contacto con '.$record->full_name)
            ->form($this->contactForm())
            ->action(function (Interested $record, array $data, InterestedCrmService $service): void {
                abort_unless(auth()->user()->can('create', InterestedContact::class), 403);
                $service->registerContact($record, auth()->user(), $data);
                unset($this->monitorData);
                Notification::make()->title('Contacto registrado')->success()->send();
            });
    }

    public function scheduleFollowUpAction(): Action
    {
        return Action::make('scheduleFollowUp')
            ->label('Programar')->icon('heroicon-o-calendar-days')->color('gray')->size('sm')
            ->record(fn (array $arguments) => Interested::query()->findOrFail($arguments['interested']))
            ->visible(fn () => auth()->user()->can('create', InterestedFollowUp::class))
            ->modalHeading(fn (Interested $record) => 'Programar seguimiento para '.$record->full_name)
            ->form($this->followUpForm())
            ->action(function (Interested $record, array $data, InterestedCrmService $service): void {
                abort_unless(auth()->user()->can('create', InterestedFollowUp::class), 403);
                $service->scheduleFollowUp($record, auth()->user(), $data);
                unset($this->monitorData);
                Notification::make()->title('Seguimiento programado')->success()->send();
            });
    }

    public function completeFollowUpAction(): Action
    {
        return Action::make('completeFollowUp')
            ->label('Completar')->icon('heroicon-o-check-circle')->color('success')->size('sm')
            ->record(fn (array $arguments) => InterestedFollowUp::query()->with('interested')->findOrFail($arguments['followUp']))
            ->visible(fn (InterestedFollowUp $record) => auth()->user()->can('complete', $record))
            ->modalHeading(fn (InterestedFollowUp $record) => 'Completar: '.$record->title)
            ->form($this->contactForm(includeContactedAt: false))
            ->action(function (InterestedFollowUp $record, array $data, InterestedCrmService $service): void {
                abort_unless(auth()->user()->can('complete', $record), 403);
                $service->completeFollowUp($record, auth()->user(), $data);
                unset($this->monitorData);
                Notification::make()->title('Seguimiento completado')->success()->send();
            });
    }

    public function rescheduleFollowUpAction(): Action
    {
        return Action::make('rescheduleFollowUp')
            ->label('Postergar')->icon('heroicon-o-clock')->color('warning')->size('sm')
            ->record(fn (array $arguments) => InterestedFollowUp::query()->findOrFail($arguments['followUp']))
            ->visible(fn (InterestedFollowUp $record) => auth()->user()->can('reschedule', $record))
            ->fillForm(fn (InterestedFollowUp $record) => [
                'due_at' => $record->due_at, 'reminder_at' => $record->reminder_at, 'assigned_user_id' => $record->assigned_user_id,
            ])
            ->form([
                Forms\Components\DateTimePicker::make('due_at')->label('Nueva fecha')->seconds(false)->required(),
                Forms\Components\DateTimePicker::make('reminder_at')->label('Recordar desde')->seconds(false),
                Forms\Components\Select::make('assigned_user_id')->label('Responsable')->options(User::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
            ])
            ->action(function (InterestedFollowUp $record, array $data, InterestedCrmService $service): void {
                abort_unless(auth()->user()->can('reschedule', $record), 403);
                $service->rescheduleFollowUp($record, $data);
                unset($this->monitorData);
                Notification::make()->title('Seguimiento reprogramado')->success()->send();
            });
    }

    private function contactForm(bool $includeContactedAt = true): array
    {
        return [
            Forms\Components\DateTimePicker::make('contacted_at')->label('Fecha del contacto')->default(now())->seconds(false)->required()->visible($includeContactedAt),
            Forms\Components\Select::make('channel')->label('Canal')->options(InterestedContact::CHANNELS)->required(),
            Forms\Components\Select::make('direction')->label('Dirección')->options(InterestedContact::DIRECTIONS)->default('outgoing')->required(),
            Forms\Components\TextInput::make('result')->label('Resultado')->placeholder('Respondió, solicitó información…')->maxLength(100),
            Forms\Components\Select::make('interested_status_id')->label('Nuevo estado comercial')->options(InterestedStatus::query()->orderBy('sort_order')->pluck('name', 'id'))->searchable(),
            Forms\Components\Textarea::make('notes')->label('Resumen del contacto')->rows(3)->columnSpanFull(),
            Forms\Components\Toggle::make('schedule_next')->label('Programar el próximo contacto')->live()->dehydrated(false),
            Forms\Components\DateTimePicker::make('next_contact_at')->label('Próximo contacto')->seconds(false)->after('contacted_at')->visible(fn (Get $get) => (bool) $get('schedule_next'))->required(fn (Get $get) => (bool) $get('schedule_next')),
            Forms\Components\TextInput::make('follow_up_title')->label('Tarea')->default('Próximo contacto')->visible(fn (Get $get) => (bool) $get('schedule_next')),
            Forms\Components\Select::make('assigned_user_id')->label('Responsable')->options(User::query()->orderBy('name')->pluck('name', 'id'))->searchable()->visible(fn (Get $get) => (bool) $get('schedule_next')),
        ];
    }

    private function followUpForm(): array
    {
        return [
            Forms\Components\TextInput::make('title')->label('Tarea')->default('Próximo contacto')->required()->maxLength(255),
            Forms\Components\Select::make('channel')->label('Canal sugerido')->options(InterestedContact::CHANNELS),
            Forms\Components\DateTimePicker::make('due_at')->label('Fecha y hora')->seconds(false)->required(),
            Forms\Components\DateTimePicker::make('reminder_at')->label('Recordar desde')->seconds(false)->helperText('Si queda vacío se avisará al llegar la fecha programada.'),
            Forms\Components\Select::make('assigned_user_id')->label('Responsable')->options(User::query()->orderBy('name')->pluck('name', 'id'))->default(auth()->id())->searchable()->required(),
            Forms\Components\Textarea::make('notes')->label('Indicaciones')->rows(3)->columnSpanFull(),
        ];
    }

    #[Computed]
    public function filterOptions(): array
    {
        return [
            'statuses' => InterestedStatus::query()->orderBy('sort_order')->pluck('name', 'id'),
            'levels' => InterestedLevel::query()->orderBy('id')->pluck('name', 'id'),
            'origins' => InterestedOrigin::query()->orderBy('name')->pluck('name', 'id'),
            'types' => InterestedType::query()->orderBy('name')->pluck('name', 'id'),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    #[Computed]
    public function monitorData(): array
    {
        $base = Interested::query();
        $query = Interested::query()->with([
            'interestedOrigins', 'interestedType', 'interestedLevel', 'interestedStatus', 'assignedUser',
            'lote.sector', 'propertie', 'latestContact.user', 'nextFollowUp.assignedUser',
        ]);

        $query->when($this->search !== '', function (Builder $query) {
            $term = '%'.trim($this->search).'%';
            $query->where(function (Builder $query) use ($term) {
                $query->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)
                    ->orWhere('dni', 'like', $term)->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)->orWhere('observations', 'like', $term)
                    ->orWhere('city', 'like', $term)->orWhere('address', 'like', $term)
                    ->orWhereHas('interestedOrigins', fn (Builder $q) => $q->where('name', 'like', $term))
                    ->orWhereHas('interestedType', fn (Builder $q) => $q->where('name', 'like', $term));
            });
        });

        $query->when($this->statusFilter !== '', fn (Builder $q) => $q->where('interested_status_id', $this->statusFilter));
        $query->when($this->levelFilter !== '', fn (Builder $q) => $q->where('interested_level_id', $this->levelFilter));
        $query->when($this->originFilter !== '', fn (Builder $q) => $q->where('interested_origins_id', $this->originFilter));
        $query->when($this->typeFilter !== '', fn (Builder $q) => $q->where('interested_type_id', $this->typeFilter));
        $query->when($this->assignedFilter !== '', fn (Builder $q) => $q->where('assigned_user_id', $this->assignedFilter));

        match ($this->timingFilter) {
            'today' => $query->whereDate('next_contact_at', today()),
            'overdue' => $query->where('next_contact_at', '<', now()),
            'without_follow_up' => $query->whereNull('next_contact_at')->whereHas('interestedStatus', fn (Builder $q) => $q->where('is_closed', false)),
            'high' => $query->whereHas('interestedLevel', fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', ['alto'])),
            'closed' => $query->whereHas('interestedStatus', fn (Builder $q) => $q->where('is_closed', true)),
            default => $query->where(fn (Builder $q) => $q->whereDoesntHave('interestedStatus')->orWhereHas('interestedStatus', fn (Builder $x) => $x->where('is_closed', false))),
        };

        match ($this->sort) {
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            'created' => $query->latest(),
            'last_contact' => $query->orderByDesc('last_contacted_at'),
            'next_contact' => $query->orderByRaw('next_contact_at IS NULL')->orderBy('next_contact_at'),
            default => $query->orderByRaw('CASE WHEN next_contact_at < NOW() THEN 0 WHEN DATE(next_contact_at) = CURDATE() THEN 1 WHEN next_contact_at IS NULL THEN 3 ELSE 2 END')->orderBy('next_contact_at'),
        };

        return [
            'records' => $query->paginate(24),
            'stats' => [
                'new' => (clone $base)->whereHas('interestedStatus', fn (Builder $q) => $q->where('name', 'Nuevo'))->count(),
                'today' => (clone $base)->whereDate('next_contact_at', today())->count(),
                'overdue' => (clone $base)->where('next_contact_at', '<', now())->count(),
                'high' => (clone $base)->whereHas('interestedLevel', fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', ['alto']))->count(),
                'without_follow_up' => (clone $base)->whereNull('next_contact_at')->whereHas('interestedStatus', fn (Builder $q) => $q->where('is_closed', false))->count(),
            ],
            'createUrl' => InterestedResource::getUrl('create'),
            'calendarUrl' => InterestedCalendar::getUrl(),
            'canCreate' => InterestedResource::canCreate(),
            'updatedAt' => now()->format('H:i:s'),
        ];
    }
}
