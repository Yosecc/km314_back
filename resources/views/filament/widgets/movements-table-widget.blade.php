<div class="overflow-x-auto">
    <table class="min-w-full text-xs text-left border border-gray-200">
        <thead>
            <tr>
                <th class="px-2 py-1 border-b">Fecha</th>
                <th class="px-2 py-1 border-b">Tipo</th>
                <th class="px-2 py-1 border-b">Descripción</th>
                <th class="px-2 py-1 border-b">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
                <tr>
                    <td class="px-2 py-1 border-b">{{ \Carbon\Carbon::parse($mov['fecha'])->format('d/m/Y') }}</td>
                    <td class="px-2 py-1 border-b">{{ $mov['tipo'] }}</td>
                    <td class="px-2 py-1 border-b">{{ $mov['descripcion'] }}</td>
                    <td class="px-2 py-1 border-b font-bold @if($mov['tipo']==='Pago') text-green-600 @elseif($mov['monto']<0) text-red-600 @else text-blue-600 @endif">
                        {{ number_format($mov['monto'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-400 py-2">Sin movimientos</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
