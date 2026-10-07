<x-filament-widgets::widget>
    <section class="fiw" wire:poll.30s>
        <header class="fiw-head">
            <div><span></span><div><h3>Formularios de incidentes</h3><p>Seguimiento de formularios pendientes de lectura</p></div></div>
            @if($canOpenList)<a href="{{ $listUrl }}">Abrir formularios <b>→</b></a>@endif
        </header>

        <div class="fiw-grid">
            @foreach($cards as $card)
                @if($canOpenList)<a href="{{ $listUrl }}" class="fiw-card {{ $card['tone'] }}">@else<div class="fiw-card {{ $card['tone'] }}">@endif
                    <div class="fiw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong>
                    <span>{{ $card['label'] }}</span>
                    <small>{{ $card['detail'] }}</small>
                @if($canOpenList)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .fiw{--fiw-bg:#fff7ed;--fiw-card:#fff;--fiw-line:#f1d2ae;--fiw-ink:#3a2415;--fiw-muted:#876d59;--fiw-orange:#df7418;overflow:hidden;border:1px solid var(--fiw-line);border-radius:18px;background:var(--fiw-bg);padding:17px;color:var(--fiw-ink);box-shadow:0 8px 24px #7a3c100d}.dark .fiw{--fiw-bg:#21150d;--fiw-card:#2d1d12;--fiw-line:#67432a;--fiw-ink:#fff5eb;--fiw-muted:#d0b49e;--fiw-orange:#ff9a45;box-shadow:0 12px 34px #0004}.fiw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.fiw-head>div{display:flex;align-items:center;gap:10px}.fiw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#f6a044,#c75a0c);box-shadow:0 0 0 4px #e87b231b}.fiw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--fiw-ink)}.fiw-head p{margin:1px 0 0;font-size:10px;color:var(--fiw-muted)}.fiw-head a{font-size:11px;font-weight:800;color:var(--fiw-orange)}.fiw-head a b{font-size:14px}.fiw-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.fiw-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--fiw-line);border-radius:16px;background:var(--fiw-card);padding:17px 18px;color:inherit;transition:.18s}.fiw-card:hover{transform:translateY(-2px);border-color:#df9b61;box-shadow:0 9px 22px #7a3c1018}.dark .fiw-card:hover{border-color:#a76d43;box-shadow:0 9px 24px #0005}.fiw-card strong{font-size:28px;line-height:1;color:var(--fiw-orange);font-weight:900}.fiw-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--fiw-ink)}.fiw-card small{margin-top:5px;font-size:9px;font-weight:650;color:var(--fiw-muted)}.fiw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#e87b2318;color:var(--fiw-orange)}.fiw-card.week strong{color:#ee8b22}.fiw-card.total strong{color:#c95618}.fiw-card.total{border-color:#e8b38f}.dark .fiw-card.total{border-color:#815033}.fiw-card.total .fiw-icon{background:#d65f1d1c;color:#d65f1d}
        @media(max-width:900px){.fiw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.fiw-card.total{grid-column:1/-1}}@media(max-width:520px){.fiw{padding:13px}.fiw-head{align-items:flex-start}.fiw-grid{grid-template-columns:1fr}.fiw-card.total{grid-column:auto}.fiw-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
