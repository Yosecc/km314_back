<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $invoice->public_identifier }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        .header { margin-bottom: 20px; }
        .items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items th, .items td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .items th { background: #f5f5f5; }
        .totals { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Factura #{{ $invoice->public_identifier }}</h2>
        <p><strong>Propietario:</strong> {{ $invoice->owner->first_name }} {{ $invoice->owner->last_name }}</p>
        <p><strong>Lote:</strong> {{ $invoice->lote->getNombre() }}</p>
        <p><strong>Periodo:</strong> {{ \Carbon\Carbon::parse($invoice->period)->format('F Y') }}</p>
        <p><strong>Fecha de vencimiento:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($invoice->status) }}</p>
        @if(isset($invoice->observations) && $invoice->observations)
            <div style="margin-top: 10px; padding: 10px; background: #f9fafb; border-left: 4px solid #2563eb;">
                <strong>Observaciones:</strong>
                <div>{!! $invoice->observations !!}</div>
            </div>
        @endif
    </div>
    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>${{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <h3>Total: ${{ number_format($invoice->total, 2) }}</h3>
    </div>
</body>
</html>
