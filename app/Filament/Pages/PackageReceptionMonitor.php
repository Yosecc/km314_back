<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Models\PackageReception;
use App\Services\PackageReceptionService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class PackageReceptionMonitor extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static string $view = 'filament.pages.package-reception-monitor';
    protected static ?string $navigationLabel = 'Monitor de paquetes';
    protected static ?string $title = 'Monitor de paquetes';
    protected static ?string $slug = 'package-reception-monitor';
    protected static ?string $navigationGroup = 'Recepción';
    protected static ?int $navigationSort = 2;

    #[Url]
    public string $status = 'active';
    public string $search = '';

    public function setStatus(string $status): void
    {
        if (in_array($status, ['active', 'expected', 'received', 'delivered', 'cancelled', 'alerts'], true)) {
            $this->status = $status;
            unset($this->monitorData);
        }
    }

    public function updatedSearch(): void { unset($this->monitorData); }

    public function receiveAction(): Action
    {
        return Action::make('receive')
            ->label('Recibir')
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('success')
            ->size('sm')
            ->modalHeading(fn (PackageReception $record) => 'Recibir '.$record->reference_code)
            ->modalDescription(fn (PackageReception $record) => $record->courier_name.' · '.$record->owner->nombres().' · '.$record->lote->getNombre())
            ->modalSubmitActionLabel('Confirmar recepción')
            ->record(fn (array $arguments) => PackageReception::query()
                ->visibleTo(auth()->user())
                ->with(['owner', 'lote'])
                ->findOrFail($arguments['reception']))
            ->visible(fn (PackageReception $record) => auth()->user()->can('receive', $record))
            ->form([
                Forms\Components\TextInput::make('received_packages_count')
                    ->label('Cantidad de bultos recibidos')->numeric()->minValue(1)
                    ->helperText('Puede dejarlo vacío si no necesita registrar una cantidad.'),
                Forms\Components\FileUpload::make('reception_files')
                    ->label('Foto al recibir')->multiple()->image()->disk('local')
                    ->directory('package-receptions/reception')->visibility('private')->maxSize(10240),
                Forms\Components\Textarea::make('reception_notes')
                    ->label('Observación')->rows(3),
            ])
            ->action(function (PackageReception $record, array $data): void {
                abort_unless(auth()->user()->can('receive', $record), 403);
                app(PackageReceptionService::class)->receive($record, auth()->user(), $data);
                unset($this->monitorData);
                Notification::make()
                    ->title('Paquete marcado como recibido')
                    ->body($record->reference_code.' ahora está pendiente de retiro.')
                    ->success()
                    ->send();
            });
    }

    #[Computed]
    public function monitorData(): array
    {
        $query = PackageReception::query()->visibleTo(auth()->user())->with(['owner', 'lote.sector', 'receivedBy', 'deliveredBy', 'cancelledBy']);
        if ($this->status === 'active') $query->whereIn('status', [PackageReception::EXPECTED, PackageReception::RECEIVED]);
        elseif ($this->status === 'alerts') $query->where(fn ($q) => $q->where(fn ($x) => $x->where('status', PackageReception::EXPECTED)->where('expected_until', '<', now()))->orWhere(fn ($x) => $x->where('status', PackageReception::RECEIVED)->where('received_at', '<=', now()->subHours(config('package-receptions.pickup_warning_hours')))));
        else $query->where('status', $this->status);

        $needle = Str::lower(Str::ascii(trim($this->search)));
        $records = $query->latest('expected_from')->limit(250)->get()->filter(function (PackageReception $r) use ($needle) {
            if ($needle === '') return true;
            return str_contains(Str::lower(Str::ascii(implode(' ', [$r->reference_code,$r->courier_name,$r->tracking_number,$r->recipient_name,$r->owner->nombres(),$r->lote->getNombre()]))), $needle);
        })->map(fn (PackageReception $r) => [
            'id'=>$r->id, 'reference'=>$r->reference_code, 'courier'=>$r->courier_name,
            'owner'=>$r->owner->nombres(), 'lot'=>$r->lote->getNombre(), 'status'=>$r->status,
            'status_label'=>PackageReception::statuses()[$r->status], 'from'=>$r->expected_from->format('d/m H:i'),
            'until'=>$r->expected_until->format('d/m H:i'), 'tracking'=>$r->tracking_number,
            'alert'=>$r->isExpectedOverdue() ? 'No llegó en el horario previsto' : match ($r->pickupAlertLevel()) {'critical'=>'Retiro demorado crítico','warning'=>'Pendiente de retiro',default=>null},
            'can_receive'=>auth()->user()->can('receive', $r),
            'url'=>PackageReceptionResource::getUrl('view', ['record'=>$r]),
        ])->values();

        $base = PackageReception::query()->visibleTo(auth()->user());
        return ['records'=>$records, 'stats'=>[
            'expected'=>(clone $base)->where('status',PackageReception::EXPECTED)->count(),
            'received'=>(clone $base)->where('status',PackageReception::RECEIVED)->count(),
            'overdue'=>(clone $base)->where('status',PackageReception::EXPECTED)->where('expected_until','<',now())->count(),
            'pickup'=>(clone $base)->where('status',PackageReception::RECEIVED)->where('received_at','<=',now()->subHours(config('package-receptions.pickup_warning_hours')))->count(),
        ], 'updated_at'=>now()->format('H:i:s'),
            'operations_url'=>PackageReceptionResource::getUrl('index'),
            'create_url'=>PackageReceptionResource::getUrl('create'),
            'can_manage'=>PackageReceptionResource::canViewAny(),
            'can_create'=>PackageReceptionResource::canCreate(),
        ];
    }
}
