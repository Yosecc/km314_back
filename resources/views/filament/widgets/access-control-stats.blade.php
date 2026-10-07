<x-filament-widgets::widget>
    <section class="acw" wire:poll.30s>
        <header class="acw-head">
            <div><span></span><div><h3>Control de Acceso</h3><p>Personas que se encuentran actualmente dentro</p></div></div>
            @if($canOpenMonitor)<a href="{{ $monitorUrl }}">Abrir monitor <b>→</b></a>@endif
        </header>

        <div class="acw-total">
            <div class="acw-total-icon"><x-heroicon-o-user-group /></div>
            <div><strong>{{ $total }}</strong><span>Personas adentro</span><small>Según el último movimiento registrado</small></div>
        </div>

        <div class="acw-grid">
            @foreach($cards as $card)
                @if($canOpenMonitor)
                    <a href="{{ $card['url'] }}" class="acw-card">
                @else
                    <div class="acw-card">
                @endif
                    <div class="acw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong>
                    <span>{{ $card['short_label'] }}</span>
                    @if(isset($card['detail']))<small>{{ $card['detail'] }}</small>@endif
                @if($canOpenMonitor)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .acw{--acw-bg:#f1f7fd;--acw-card:#fff;--acw-line:#d3e2f0;--acw-ink:#152b43;--acw-muted:#687d92;--acw-blue:#236faf;overflow:hidden;border:1px solid var(--acw-line);border-radius:18px;background:var(--acw-bg);padding:17px;color:var(--acw-ink);box-shadow:0 8px 24px #173d5d0d}.dark .acw{--acw-bg:#101a26;--acw-card:#172536;--acw-line:#344d65;--acw-ink:#eef6ff;--acw-muted:#a8bbcd;--acw-blue:#6eb7f4;box-shadow:0 12px 34px #0004}.acw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.acw-head>div{display:flex;align-items:center;gap:10px}.acw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#4b9ee7,#165b98);box-shadow:0 0 0 4px #2f83cb1c}.acw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--acw-ink)}.acw-head p{margin:1px 0 0;font-size:10px;color:var(--acw-muted)}.acw-head a{font-size:11px;font-weight:800;color:var(--acw-blue)}.acw-head a b{font-size:14px}.acw-total{display:flex;align-items:center;gap:12px;margin-bottom:13px;border:1px solid #8dbce4;border-radius:16px;background:linear-gradient(120deg,#155b96,#237ebb);padding:15px 18px;color:#fff;box-shadow:0 8px 20px #185f9720}.acw-total-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#ffffff1c}.acw-total-icon svg{width:22px}.acw-total strong,.acw-total span,.acw-total small{display:block}.acw-total strong{font-size:28px;line-height:1;font-weight:900}.acw-total span{margin-top:5px;font-size:12px;font-weight:850}.acw-total small{margin-top:2px;color:#d8edff;font-size:9px}.acw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.acw-card{position:relative;display:flex;min-height:108px;flex-direction:column;justify-content:center;border:1px solid var(--acw-line);border-radius:16px;background:var(--acw-card);padding:16px 18px;color:inherit;transition:.18s}.acw-card:hover{transform:translateY(-2px);border-color:#78acd8;box-shadow:0 9px 22px #1d5e9318}.dark .acw-card:hover{border-color:#537da3;box-shadow:0 9px 24px #0005}.acw-card strong{font-size:27px;line-height:1;color:var(--acw-blue);font-weight:900}.acw-card>span{margin-top:8px;font-size:11px;font-weight:800;color:var(--acw-ink)}.acw-card small{margin-top:4px;font-size:8px;font-weight:650;color:var(--acw-muted)}.acw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#2e7fbe15;color:#2b75b2}.dark .acw-icon{background:#6eb7f41c;color:#78c2ff}
        @media(max-width:900px){.acw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.acw{padding:13px}.acw-head{align-items:flex-start}.acw-grid{grid-template-columns:1fr}.acw-card{min-height:100px}}
    </style>
</x-filament-widgets::widget>
