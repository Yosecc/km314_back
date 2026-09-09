<?php
namespace App\Filament\Resources\PackageReceptionResource\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Models\PackageReception;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Services\PackageReceptionService;

class ViewPackageReception extends ViewRecord
{
    protected static string $resource = PackageReceptionResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->user()->can('update', $this->record)),
            Actions\Action::make('receive')->label('Recibir paquete')->icon('heroicon-o-inbox-arrow-down')->color('success')
                ->visible(fn () => $this->record->status === PackageReception::EXPECTED && auth()->user()->can('receive', $this->record))->form([
                    Forms\Components\TextInput::make('received_packages_count')->label('Cantidad de bultos')->numeric()->minValue(1),
                    Forms\Components\FileUpload::make('reception_files')->label('Foto al recibir')->multiple()->image()->disk('local')->directory('package-receptions/reception')->visibility('private')->maxSize(10240),
                    Forms\Components\Textarea::make('reception_notes')->label('Observación'),
                ])->action(function (array $data) {
                    abort_unless(auth()->user()->can('receive', $this->record), 403);
                    app(PackageReceptionService::class)->receive($this->record, auth()->user(), $data);
                    Notification::make()->title('Paquete marcado como recibido')->success()->send();
                    $this->redirect(PackageReceptionResource::getUrl('view', ['record'=>$this->record]));
                }),
            Actions\Action::make('deliver')->label('Entregar paquete')->icon('heroicon-o-hand-raised')->color('primary')
                ->visible(fn () => $this->record->status === PackageReception::RECEIVED && auth()->user()->can('deliver', $this->record))->form([
                    Forms\Components\Radio::make('delivered_to_type')->label('¿Quién retira?')->options(['owner'=>'Propietario','third_party'=>'Otra persona'])->default('owner')->inline()->live()->required(),
                    Forms\Components\TextInput::make('delivered_to_name')->label('Nombre completo')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party'),
                    Forms\Components\TextInput::make('delivered_to_dni')->label('DNI')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party'),
                    Forms\Components\FileUpload::make('delivery_identity_files')->label('Imagen del DNI')->multiple()->image()->disk('local')->directory('package-receptions/delivery-identity')->visibility('private')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party')->maxSize(10240),
                    Forms\Components\Textarea::make('delivery_notes')->label('Observación'),
                ])->action(function (array $data) {
                    abort_unless(auth()->user()->can('deliver', $this->record), 403);
                    app(PackageReceptionService::class)->deliver($this->record, auth()->user(), $data);
                    Notification::make()->title('Entrega registrada')->success()->send();
                    $this->redirect(PackageReceptionResource::getUrl('view', ['record'=>$this->record]));
                }),
            Actions\Action::make('cancel')->label('Cancelar')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => $this->record->status === PackageReception::EXPECTED && auth()->user()->can('cancel', $this->record))->requiresConfirmation()->form([
                    Forms\Components\Textarea::make('cancellation_reason')->label('Motivo de cancelación')->required(),
                ])->action(function (array $data) {
                    abort_unless(auth()->user()->can('cancel', $this->record), 403);
                    app(PackageReceptionService::class)->cancel($this->record, auth()->user(), $data);
                    Notification::make()->title('Recepción cancelada')->success()->send();
                    $this->redirect(PackageReceptionResource::getUrl('view', ['record'=>$this->record]));
                }),
        ];
    }
}
