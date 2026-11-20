<x-filament-panels::page>
    <div style="margin-bottom:1rem;border:1px solid rgb(139 92 246 / .22);border-radius:18px;background:linear-gradient(120deg,rgb(91 52 131),rgb(122 74 163));padding:20px 24px;color:white">
        <p style="font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#ead8fb">Agenda comercial</p>
        <h2 style="margin-top:4px;font-size:22px;font-weight:900">Contactos y seguimientos</h2>
        <p style="margin-top:4px;font-size:11px;color:#eee2f8">Seleccioná una fecha para programar una tarea. Abrí un evento para editarlo o marcarlo desde el monitor.</p>
        <a href="{{ \App\Filament\Pages\InterestedMonitor::getUrl() }}" style="display:inline-block;margin-top:10px;border-radius:9px;background:white;padding:7px 11px;font-size:10px;font-weight:800;color:#663b8f">Volver al monitor</a>
    </div>
    @livewire(\App\Filament\Widgets\InterestedCalendarWidget::class)
</x-filament-panels::page>
