<x-filament-widgets::widget>
    <section class="fcw" wire:poll.30s>
        <header class="fcw-head">
            <div><span></span><div><h3>Formularios de Control</h3><p>Resumen del mes actual · actualizado en tiempo real</p></div></div>
            @if($canOpenMonitor)<a href="{{ $monitorUrl }}">Abrir monitor <b>→</b></a>@endif
        </header>

        <div class="fcw-grid" style="--fcw-columns: {{ count($cards) }}">
            @foreach($cards as $card)
                @if($canOpenMonitor)
                    <a href="{{ $monitorUrl }}?status={{ $card['status'] }}" class="fcw-card {{ $card['tone'] }}">
                @else
                    <div class="fcw-card {{ $card['tone'] }}">
                @endif
                    <div class="fcw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong>
                    <span>{{ $card['label'] }}</span>
                    @if(isset($card['detail']))<small>{{ $card['detail'] }}</small>@endif
                @if($canOpenMonitor)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .fcw{--fcw-bg:#f7f3fc;--fcw-card:#fff;--fcw-line:#ddd5eb;--fcw-ink:#2b213d;--fcw-muted:#756b87;--fcw-purple:#7b55c7;overflow:hidden;border:1px solid var(--fcw-line);border-radius:18px;background:var(--fcw-bg);padding:17px;color:var(--fcw-ink);box-shadow:0 8px 24px #3e28650c}.dark .fcw{--fcw-bg:#16111f;--fcw-card:#211a2e;--fcw-line:#493b61;--fcw-ink:#f4efff;--fcw-muted:#bdb0d2;--fcw-purple:#ac89ff;box-shadow:0 12px 34px #0004}.fcw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.fcw-head>div{display:flex;align-items:center;gap:10px}.fcw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#9c73eb,#6942ac);box-shadow:0 0 0 4px #8b62d71a}.fcw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--fcw-ink)}.fcw-head p{margin:1px 0 0;font-size:10px;color:var(--fcw-muted)}.fcw-head a{font-size:11px;font-weight:800;color:var(--fcw-purple)}.fcw-head a b{font-size:14px}.fcw-grid{display:grid;grid-template-columns:repeat(var(--fcw-columns),minmax(0,1fr));gap:13px}.fcw-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--fcw-line);border-radius:16px;background:var(--fcw-card);padding:17px 18px;color:inherit;transition:.18s}.fcw-card:hover{transform:translateY(-2px);border-color:#9a79cf;box-shadow:0 9px 22px #44286e18}.dark .fcw-card:hover{border-color:#725b94;box-shadow:0 9px 24px #0005}.fcw-card strong{font-size:28px;line-height:1;color:var(--fcw-purple);font-weight:900}.fcw-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--fcw-ink)}.fcw-card small{margin-top:5px;font-size:9px;font-weight:650;color:var(--fcw-muted)}.fcw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#8d68cf14;color:#8d68cf}.dark .fcw-icon{background:#ae8df01c;color:#b89bf0}.fcw-card.authorized strong{color:#26936c}.fcw-card.authorized .fcw-icon{background:#2f9c7415;color:#29936d}.fcw-card.attention strong{color:#dc831e}.fcw-card.attention .fcw-icon{background:#e3932418;color:#df8b20}.fcw-card.attention{border-color:#e7c48f}.dark .fcw-card.attention{border-color:#725735}
        @media(max-width:900px){.fcw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.fcw{padding:13px}.fcw-head{align-items:flex-start}.fcw-grid{grid-template-columns:1fr}.fcw-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
