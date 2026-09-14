<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ServiceRequestMonitor extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static string $view = 'filament.pages.service-request-monitor';
    protected static ?string $navigationLabel = 'Monitor de solicitudes';
    protected static ?string $title = 'Monitor de Solicitudes de Servicio';
    protected static ?string $slug = 'service-request-monitor';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?int $navigationSort = 2;

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $month = '';

    public string $search = '';

    public function mount(): void
    {
        $this->month = $this->month ?: now('America/Argentina/Buenos_Aires')->format('Y-m');
    }

    public function setStatus(string $status): void
    {
        if (in_array($status, ['active', 'all', 'attention', 'pending', 'in_progress', 'completed', 'rejected', 'cancelled'], true)) {
            $this->status = $status;
            unset($this->monitorData);
        }
    }

    public function updatedSearch(): void
    {
        unset($this->monitorData);
    }

    public function updatedMonth(): void
    {
        unset($this->monitorData);
    }

    public function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Actualizar estado')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->size('sm')
            ->record(fn (array $arguments) => $this->baseQuery()->findOrFail($arguments['request']))
            ->visible(fn (ServiceRequest $record) => ! ServiceRequestResource::isOwnerContext() && Auth::user()->can('update', $record))
            ->form([
                Select::make('service_request_status_id')->label('Nuevo estado')->options(fn () => ServiceRequestStatus::options())->required(),
                Select::make('asignado_status_id')->label('Asignar a')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                Forms\Components\Textarea::make('resolution_notes')->label('Nota para el propietario')->rows(3),
            ])
            ->fillForm(fn (ServiceRequest $record) => [
                'service_request_status_id' => $record->service_request_status_id,
                'asignado_status_id' => $record->asignado_status_id,
                'resolution_notes' => $record->resolution_notes,
            ])
            ->action(function (ServiceRequest $record, array $data): void {
                abort_unless(Auth::user()->can('update', $record), 403);
                $record->fill(['asignado_status_id' => $data['asignado_status_id'] ?? null]);
                $record->transitionTo(ServiceRequestStatus::findOrFail($data['service_request_status_id']), Auth::user(), $data['resolution_notes'] ?? null);
                unset($this->monitorData);
                Notification::make()->title('Estado actualizado')->success()->send();
            });
    }

    #[Computed]
    public function monitorData(): array
    {
        $requests = $this->baseQuery()->with(['owner', 'lote.sector', 'service', 'serviceRequestStatus', 'userAsignado'])->latest()->get()
            ->map(fn (ServiceRequest $request) => $this->mapRequest($request));
        $needle = Str::lower(Str::ascii(trim($this->search)));
        $visible = $requests
            ->when($this->status === 'active', fn ($rows) => $rows->whereIn('status_code', ['pending', 'in_progress']))
            ->when($this->status === 'attention', fn ($rows) => $rows->whereIn('status_code', ['rejected', 'cancelled']))
            ->when(! in_array($this->status, ['active', 'all', 'attention'], true), fn ($rows) => $rows->where('status_code', $this->status))
            ->when($needle !== '', fn ($rows) => $rows->filter(fn (array $row) => str_contains($row['search_text'], $needle)))
            ->values();

        return [
            'requests' => $visible,
            'stats' => [
                'pending' => $requests->where('status_code', 'pending')->count(),
                'in_progress' => $requests->where('status_code', 'in_progress')->count(),
                'completed' => $requests->where('status_code', 'completed')->count(),
                'attention' => $requests->whereIn('status_code', ['rejected', 'cancelled'])->count(),
                'rejected' => $requests->where('status_code', 'rejected')->count(),
                'cancelled' => $requests->where('status_code', 'cancelled')->count(),
            ],
            'updated_at' => now()->format('H:i:s'),
            'list_url' => ServiceRequestResource::getUrl('index'),
            'create_url' => ServiceRequestResource::getUrl('create'),
            'can_create' => ServiceRequestResource::canCreate(),
            'can_manage' => ServiceRequestResource::canViewAny(),
        ];
    }

    private function mapRequest(ServiceRequest $request): array
    {
        $code = $request->statusCode() ?: 'other';
        $status = $request->serviceRequestStatus?->name ?? 'Sin estado';
        $lot = $request->lote?->getNombre() ?? 'Sin lote';
        $owner = $request->owner?->nombres() ?? 'Sin propietario';

        return [
            'id' => $request->id,
            'title' => $request->name,
            'service' => $request->service?->name ?? 'Servicio sin definir',
            'lot' => $lot,
            'owner' => $owner,
            'status' => $status,
            'status_code' => $code,
            'created' => $request->created_at?->format('d/m/Y H:i'),
            'scheduled' => $request->starts_at?->format('d/m/Y H:i') ?? 'Sin fecha programada',
            'assigned' => $request->userAsignado?->name,
            'can_edit' => ServiceRequestResource::canEdit($request),
            'can_change_status' => ! ServiceRequestResource::isOwnerContext() && Auth::user()->can('update', $request),
            'url' => ServiceRequestResource::getUrl('edit', ['record' => $request]),
            'search_text' => Str::lower(Str::ascii(implode(' ', [$request->id, $request->name, $request->service?->name, $lot, $owner, $status]))),
        ];
    }

    private function baseQuery(): Builder
    {
        [$from, $to] = $this->monthRange();
        return ServiceRequest::query()->visibleTo(Auth::user())->whereBetween('created_at', [$from, $to]);
    }

    private function monthRange(): array
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', $this->month, 'America/Argentina/Buenos_Aires');
        } catch (\Throwable) {
            $month = now('America/Argentina/Buenos_Aires')->startOfMonth();
            $this->month = $month->format('Y-m');
        }

        return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
    }
}
