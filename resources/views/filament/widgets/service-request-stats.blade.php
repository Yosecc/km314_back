<x-filament-widgets::widget>
    <section class="srw" wire:poll.30s>
        <header class="srw-head">
            <div><span></span><div><h3>Solicitudes de servicio</h3><p>Resumen del mes actual · actualizado en tiempo real</p></div></div>
            @if($canOpenMonitor)<a href="{{ $monitorUrl }}">Abrir monitor <b>→</b></a>@endif
        </header>

        <div class="srw-grid">
            @foreach($cards as $card)
                @if($canOpenMonitor)
                    <a href="{{ $monitorUrl }}?status={{ $card['status'] }}" class="srw-card {{ $card['tone'] }}">
                @else
                    <div class="srw-card {{ $card['tone'] }}">
                @endif
                    <div class="srw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong>
                    <span>{{ $card['label'] }}</span>
                @if($canOpenMonitor)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .srw{--srw-bg:#f0f8fa;--srw-card:#fff;--srw-line:#cfe4e8;--srw-ink:#19323a;--srw-muted:#647981;--srw-blue:#167b93;overflow:hidden;border:1px solid var(--srw-line);border-radius:18px;background:var(--srw-bg);padding:17px;color:var(--srw-ink);box-shadow:0 8px 24px #153f4b0d}.dark .srw{--srw-bg:#0f1c20;--srw-card:#17282d;--srw-line:#34545d;--srw-ink:#edf9fb;--srw-muted:#a7bec4;--srw-blue:#5bc5df;box-shadow:0 12px 34px #0004}.srw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.srw-head>div{display:flex;align-items:center;gap:10px}.srw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#3eacc5,#106c84);box-shadow:0 0 0 4px #218ba51c}.srw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--srw-ink)}.srw-head p{margin:1px 0 0;font-size:10px;color:var(--srw-muted)}.srw-head a{font-size:11px;font-weight:800;color:var(--srw-blue)}.srw-head a b{font-size:14px}.srw-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.srw-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--srw-line);border-radius:16px;background:var(--srw-card);padding:17px 18px;color:inherit;transition:.18s}.srw-card:hover{transform:translateY(-2px);border-color:#6eb8c7;box-shadow:0 9px 22px #153f4b18}.dark .srw-card:hover{border-color:#4d7f8b;box-shadow:0 9px 24px #0005}.srw-card strong{font-size:28px;line-height:1;color:var(--srw-blue);font-weight:900}.srw-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--srw-ink)}.srw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#1987a114;color:#167b93}.dark .srw-icon{background:#5bc5df1c;color:#74d7ee}.srw-card.pending strong{color:#d38716}.srw-card.pending .srw-icon{background:#e3932418;color:#df8b20}.srw-card.progress strong{color:#3178cb}.srw-card.progress .srw-icon{background:#3178cb18;color:#3178cb}.srw-card.pending{border-color:#e7c48f}.srw-card.progress{border-color:#a8c8e9}.dark .srw-card.pending{border-color:#725735}.dark .srw-card.progress{border-color:#36597c}
        @media(max-width:900px){.srw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.srw{padding:13px}.srw-head{align-items:flex-start}.srw-grid{grid-template-columns:1fr}.srw-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
