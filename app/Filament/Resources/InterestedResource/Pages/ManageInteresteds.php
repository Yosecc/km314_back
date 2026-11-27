<?php

namespace App\Filament\Resources\InterestedResource\Pages;

use App\Filament\Pages\InterestedCalendar;
use App\Filament\Pages\InterestedMonitor;
use App\Filament\Resources\InterestedResource;
use App\Services\InterestedCsv;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Validation\ValidationException;

class ManageInteresteds extends ManageRecords
{
    protected static string $resource = InterestedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('monitor')
                ->label('Abrir monitor')
                ->icon('heroicon-o-signal')
                ->url(InterestedMonitor::getUrl())
                ->visible(fn () => InterestedMonitor::canAccess()),
            Actions\Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->url(InterestedCalendar::getUrl())
                ->visible(fn () => InterestedCalendar::canAccess()),
            Actions\CreateAction::make(),
            Actions\Action::make('exportCsv')
                ->label('Exportar CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => InterestedResource::canViewAny() && auth()->user()->can('export_interested'))
                ->action(function (InterestedCsv $csv) {
                    abort_unless(InterestedResource::canViewAny() && auth()->user()->can('export_interested'), 403);

                    return response()->streamDownload(function () use ($csv) {
                        $stream = fopen('php://output', 'wb');
                        try {
                            $csv->export($stream);
                        } finally {
                            fclose($stream);
                        }
                    }, 'interesados-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            Actions\Action::make('importCsv')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn () => InterestedResource::canCreate() && auth()->user()->can('import_interested'))
                ->modalDescription('Usá el encabezado de Exportar CSV. Se omiten emails ya registrados, incluso en la papelera. Cada fila sin email se agrega como un interesado nuevo.')
                ->form([
                    FileUpload::make('csv')->label('Archivo CSV UTF-8')->acceptedFileTypes([
                        'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel',
                    ])->maxSize(10240)->storeFiles(false)->required(),
                ])
                ->action(function (array $data, InterestedCsv $csv): void {
                    abort_unless(InterestedResource::canCreate() && auth()->user()->can('import_interested'), 403);

                    $stream = $data['csv']->readStream();
                    if (! is_resource($stream)) {
                        throw ValidationException::withMessages(['csv' => 'No se pudo leer el archivo.']);
                    }

                    try {
                        $result = $csv->import($stream, auth()->user()->can('assign_interested'));
                    } catch (ValidationException $exception) {
                        Notification::make()->title('No se importó el archivo')->body($exception->getMessage())->danger()->send();

                        return;
                    } finally {
                        fclose($stream);
                    }

                    Notification::make()->title('Importación terminada')
                        ->body("Agregados: {$result['created']}. Omitidos por email repetido: {$result['skipped']}.")
                        ->success()->send();
                }),
        ];
    }
}
