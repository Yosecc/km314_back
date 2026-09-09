<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FormControlResource;
use App\Filament\Resources\FormControlResource\Pages\Concerns\HasPublicFormLinkAction;
use App\Models\FormControl;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class FormControlMonitor extends Page
{
    use HasPageShield;
    use HasPublicFormLinkAction;

    protected static ?string $navigationIcon = 'heroicon-o-signal';


    protected static string $view = 'filament.pages.form-control-monitor';

    protected static ?string $navigationLabel = 'Monitor de formularios';

    protected static ?string $title = 'Monitor de Formularios de Control';

    protected static ?string $slug = 'form-control-monitor';

    protected static ?string $navigationGroup = 'Control de acceso';

    protected static ?int $navigationSort = 3;

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $month = '';

    public string $search = '';

    public function mount(): void
    {
        if ($this->month === '') {
            $this->month = now('America/Argentina/Buenos_Aires')->format('Y-m');
        }
    }

    public function setStatus(string $status): void
    {
        if (in_array($status, ['active', 'all', 'attention', 'OwnerPending', 'Pending', 'Authorized', 'Denied', 'Vencido', 'Expirado'], true)) {
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

    public function qrAction(): Action
    {
        return Action::make('qr')->label('Ver QR')->icon('heroicon-o-qr-code')->color('info')->size('sm')
            ->record(fn (array $arguments) => $this->findVisibleForm($arguments['form']))
            ->modalHeading(fn (FormControl $record) => 'Código QR · Formulario #'.$record->id)
            ->modalContent(fn (FormControl $record) => view('components.qr-modal', ['record' => $record, 'entityType' => 'Formulario de Control']))
            ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar');
    }

    public function ownerApproveAction(): Action
    {
        return Action::make('ownerApprove')->label('Aprobar solicitud')->icon('heroicon-o-check-circle')->color('success')->size('sm')
            ->record(fn (array $arguments) => $this->findVisibleForm($arguments['form']))
            ->visible(fn (FormControl $record) => auth()->user()->hasRole('owner') && $record->status === 'OwnerPending')
            ->requiresConfirmation()->modalDescription('Luego de su aprobación, administración podrá revisar este formulario.')
            ->action(function (FormControl $record): void {
                $record->approveByOwner(auth()->user());
                $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'Administrador']))->get();
                Notification::make()->title('Formulario pendiente de aprobación administrativa')->body('El propietario aprobó el formulario #'.$record->id.'.')->sendToDatabase($admins);
                unset($this->monitorData);
                Notification::make()->title('Formulario enviado a administración')->success()->send();
            });
    }

    public function adminApproveAction(): Action
    {
        return Action::make('adminApprove')->label('Aprobar')->icon('heroicon-o-check')->color('success')->size('sm')
            ->record(fn (array $arguments) => $this->findVisibleForm($arguments['form']))
            ->visible(fn (FormControl $record) => $this->isAdmin() && auth()->user()->can('aprobar_form::control') && $record->status === 'Pending' && ! $record->isExpirado())
            ->requiresConfirmation()->action(function (FormControl $record): void {
                abort_unless($this->isAdmin() && auth()->user()->can('aprobar_form::control'), 403);
                $record->aprobar();
                $this->notifyOwner($record, 'Formulario aprobado', 'Las personas configuradas ya pueden acceder según los horarios establecidos.');
                unset($this->monitorData);
                Notification::make()->title('Formulario aprobado')->success()->send();
            });
    }

    public function rejectAction(): Action
    {
        return Action::make('reject')->label('Rechazar')->icon('heroicon-o-x-circle')->color('danger')->size('sm')
            ->record(fn (array $arguments) => $this->findVisibleForm($arguments['form']))
            ->visible(fn (FormControl $record) => $this->isAdmin() && auth()->user()->can('rechazar_form::control') && ! in_array($record->status, ['Denied', 'Authorized'], true))
            ->requiresConfirmation()->action(function (FormControl $record): void {
                abort_unless($this->isAdmin() && auth()->user()->can('rechazar_form::control'), 403);
                $record->rechazar();
                $this->notifyOwner($record, 'Formulario rechazado', 'Administración rechazó el formulario #'.$record->id.'.');
                unset($this->monitorData);
                Notification::make()->title('Formulario rechazado')->success()->send();
            });
    }

    public function deleteFormAction(): Action
    {
        return Action::make('deleteForm')->label('Borrar')->icon('heroicon-o-trash')->color('danger')->size('sm')
            ->record(fn (array $arguments) => $this->findVisibleFormIncludingDeleted($arguments['form']))
            ->visible(fn (FormControl $record) => auth()->user()->can('delete', $record) && in_array($record->statusComputed(), ['Denied', 'Vencido', 'Expirado'], true))
            ->requiresConfirmation()
            ->modalHeading('Borrar formulario')
            ->modalDescription('El formulario dejará de aparecer en el monitor. Esta acción utiliza la papelera del sistema.')
            ->action(function (FormControl $record): void {
                abort_unless(auth()->user()->can('delete', $record) && in_array($record->statusComputed(), ['Denied', 'Vencido', 'Expirado'], true), 403);
                $record->delete();
                unset($this->monitorData);
                Notification::make()->title('Formulario borrado')->success()->send();
            });
    }

    #[Computed]
    public function monitorData(): array
    {
        $forms = $this->baseQuery()->with(['owner', 'peoples', 'dateRanges'])->latest()->get()->map(fn (FormControl $form) => $this->mapForm($form));
        $attentionStatuses = $this->isAdmin()
            ? ['Vencido', 'Expirado']
            : ['Denied', 'Vencido', 'Expirado'];
        $needle = Str::lower(Str::ascii(trim($this->search)));
        $visible = $forms->when($this->status === 'active', fn (Collection $rows) => $rows->whereIn('computed_status', ['OwnerPending', 'Pending', 'Authorized', 'Vencido']))
            ->when($this->status === 'attention', fn (Collection $rows) => $rows->whereIn('computed_status', $attentionStatuses))
            ->when(! in_array($this->status, ['active', 'all', 'attention'], true), fn (Collection $rows) => $rows->where('computed_status', $this->status))
            ->when($needle !== '', fn (Collection $rows) => $rows->filter(fn ($row) => str_contains($row['search_text'], $needle)))->values();

        return ['forms' => $visible, 'stats' => [
            'owner_pending' => $forms->where('computed_status', 'OwnerPending')->count(),
            'pending' => $forms->where('computed_status', 'Pending')->count(),
            'authorized' => $forms->where('computed_status', 'Authorized')->count(),
            'denied' => $forms->where('computed_status', 'Denied')->count(),
            'overdue' => $forms->where('computed_status', 'Vencido')->count(),
            'expired' => $forms->where('computed_status', 'Expirado')->count(),
            'attention' => $forms->whereIn('computed_status', $attentionStatuses)->count(),
            'attention_includes_denied' => ! $this->isAdmin(),
        ], 'updated_at' => now()->format('H:i:s'), 'list_url' => FormControlResource::getUrl('index'),
            'create_url' => FormControlResource::getUrl('create'), 'can_create' => FormControlResource::canCreate()];
    }

    private function mapForm(FormControl $form): array
    {
        $status = $form->statusComputed();
        $range = $form->dateRanges->first();
        $startDate = $range?->start_date_range ?: $form->start_date_range;
        $startTime = $range?->start_time_range ?: $form->start_time_range;
        $endDate = $range?->end_date_range ?: $form->end_date_range;
        $endTime = $range?->end_time_range ?: $form->end_time_range;
        $lots = collect($form->lote_ids)->flatten()->filter()->implode(' · ') ?: 'Sin lote';
        $labels = ['OwnerPending' => 'Pendiente del propietario', 'Pending' => 'Pendiente de administración', 'Authorized' => 'Autorizado', 'Denied' => 'Rechazado', 'Vencido' => 'Vencido', 'Expirado' => 'Expirado'];

        return ['id' => $form->id, 'lot' => $lots, 'owner' => $form->owner?->nombres() ?: 'Sin propietario', 'income' => collect($form->income_type)->filter()->implode(', ') ?: 'Sin tipo',
            'people' => $form->peoples->count(), 'computed_status' => $status, 'status_label' => $labels[$status] ?? $status,
            'range' => $this->formatRange($startDate, $startTime, $endDate, $endTime), 'created' => $form->created_at?->format('d/m/Y H:i'),
            'can_edit' => auth()->user()->can('update', $form) && (! auth()->user()->hasRole('owner') || in_array($form->status, ['OwnerPending', 'Pending'], true)),
            'can_owner_approve' => auth()->user()->hasRole('owner') && $form->status === 'OwnerPending',
            'can_admin_approve' => $this->isAdmin() && auth()->user()->can('aprobar_form::control') && $form->status === 'Pending' && ! $form->isExpirado(),
            'can_reject' => $this->isAdmin() && auth()->user()->can('rechazar_form::control') && ! in_array($form->status, ['Denied', 'Authorized'], true),
            'can_delete' => auth()->user()->can('delete', $form) && in_array($status, ['Denied', 'Vencido', 'Expirado'], true),
            'view_url' => FormControlResource::getUrl('view', ['record' => $form]), 'edit_url' => FormControlResource::getUrl('edit', ['record' => $form]),
            'search_text' => Str::lower(Str::ascii(implode(' ', [$form->id, $lots, $form->owner?->nombres(), collect($form->income_type)->implode(' '), $labels[$status] ?? $status]))),
        ];
    }

    private function formatRange($startDate, $startTime, $endDate, $endTime): string
    {
        if (! $startDate) {
            return 'Sin fechas';
        }
        $start = Carbon::parse($startDate.' '.($startTime ?: '00:00'))->format('d/m/Y H:i');
        $end = $endDate ? Carbon::parse($endDate.' '.($endTime ?: '23:59'))->format('d/m/Y H:i') : 'Sin límite';

        return $start.' — '.$end;
    }

    private function baseQuery(): Builder
    {
        $query = FormControl::query();
        [$monthStart, $monthEnd] = $this->monthRange();
        $query->whereBetween('created_at', [$monthStart, $monthEnd]);

        return auth()->user()->hasRole('owner') ? $query->where('owner_id', auth()->user()->owner_id) : $query->where('status', '!=', 'OwnerPending');
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

    private function findVisibleForm(int $id): FormControl
    {
        return $this->baseQuery()->with(['owner', 'peoples', 'dateRanges'])->findOrFail($id);
    }

    private function findVisibleFormIncludingDeleted(int $id): FormControl
    {
        return $this->baseQuery()->withTrashed()->with(['owner', 'peoples', 'dateRanges'])->findOrFail($id);
    }

    private function isAdmin(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'Administrador']);
    }

    private function notifyOwner(FormControl $record, string $title, string $body): void
    {
        if ($record->owner?->user) {
            Notification::make()->title($title)->body($body)->actions([NotificationAction::make('ver')->label('Ver')->url(FormControlResource::getUrl('view', ['record' => $record]))])->sendToDatabase($record->owner->user);
        }
    }
}
