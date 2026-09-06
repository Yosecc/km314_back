<x-filament-widgets::widget>
    <section class="prw" wire:poll.30s>
        <header class="prw-head">
            <div><span></span><div><h3>Recepción de Paquetes</h3><p>Resumen actualizado en tiempo real</p></div></div>
            @if($canOpenMonitor)<a href="{{ $monitorUrl }}">Abrir monitor <b>→</b></a>@endif
        </header>

        <div class="prw-grid">
            @foreach($cards as $card)
                @if($canOpenMonitor)
                    <a href="{{ $monitorUrl }}?status={{ $card['status'] }}" class="prw-card {{ $card['tone'] }}">
                @else
                    <div class="prw-card {{ $card['tone'] }}">
                @endif
                    <div class="prw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong>
                    <span>{{ $card['label'] }}</span>
                    @if(isset($card['detail']))<small>{{ $card['detail'] }}</small>@endif
                @if($canOpenMonitor)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .prw{--prw-bg:#f1f8f5;--prw-card:#fff;--prw-line:#d4e5de;--prw-ink:#182a24;--prw-muted:#687a73;--prw-green:#258264;overflow:hidden;border:1px solid var(--prw-line);border-radius:18px;background:var(--prw-bg);padding:17px;color:var(--prw-ink);box-shadow:0 8px 24px #173d300d}.dark .prw{--prw-bg:#101b17;--prw-card:#172720;--prw-line:#355148;--prw-ink:#eff9f5;--prw-muted:#a9beb5;--prw-green:#62d8ae;box-shadow:0 12px 34px #0004}.prw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.prw-head>div{display:flex;align-items:center;gap:10px}.prw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#48b78f,#176b53);box-shadow:0 0 0 4px #2c98721c}.prw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--prw-ink)}.prw-head p{margin:1px 0 0;font-size:10px;color:var(--prw-muted)}.prw-head a{font-size:11px;font-weight:800;color:var(--prw-green)}.prw-head a b{font-size:14px}.prw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.prw-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--prw-line);border-radius:16px;background:var(--prw-card);padding:17px 18px;color:inherit;transition:.18s}.prw-card:hover{transform:translateY(-2px);border-color:#79b9a2;box-shadow:0 9px 22px #173d3018}.dark .prw-card:hover{border-color:#568272;box-shadow:0 9px 24px #0005}.prw-card strong{font-size:28px;line-height:1;color:var(--prw-green);font-weight:900}.prw-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--prw-ink)}.prw-card small{margin-top:5px;font-size:9px;font-weight:650;color:var(--prw-muted)}.prw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#29936d16;color:#258264}.dark .prw-icon{background:#62d8ae1c;color:#69ddb5}.prw-card.overdue strong{color:#d98b10}.prw-card.overdue .prw-icon{background:#e3932418;color:#df8b20}.prw-card.pickup strong{color:#e15050}.prw-card.pickup .prw-icon{background:#e1505018;color:#e15050}.prw-card.overdue{border-color:#e7c48f}.prw-card.pickup{border-color:#e4aaaa}.dark .prw-card.overdue{border-color:#725735}.dark .prw-card.pickup{border-color:#704545}
        @media(max-width:900px){.prw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.prw{padding:13px}.prw-head{align-items:flex-start}.prw-grid{grid-template-columns:1fr}.prw-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
