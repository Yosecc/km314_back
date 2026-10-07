<x-filament-widgets::widget>
    <section class="ew" wire:poll.30s>
        <header class="ew-head">
            <div><span></span><div><h3>Gestión de Trabajadores</h3><p>Solicitudes registradas y estado de aprobación</p></div></div>
            @if($canManage)<a href="{{ $resourceUrl }}">Gestionar trabajadores <b>→</b></a>@endif
        </header>

        @if($hasPending)
            <div class="ew-alert"><x-heroicon-o-bell-alert /><div><strong>Hay trabajadores pendientes por aprobar</strong><span>Revisá las solicitudes para aprobarlas o rechazarlas.</span></div></div>
        @endif

        <div class="ew-grid">
            @foreach($cards as $card)
                @php($cardUrl = $card['status'] ? $resourceUrl.'?'.http_build_query(['tableFilters'=>['status'=>['value'=>$card['status']]]]) : $resourceUrl)
                @if($canManage)<a href="{{ $cardUrl }}" class="ew-card {{ $card['tone'] }}">@else<div class="ew-card {{ $card['tone'] }}">@endif
                    <div class="ew-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong><span>{{ $card['label'] }}</span>
                    @if(isset($card['detail']))<small>{{ $card['detail'] }}</small>@endif
                @if($canManage)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .ew{--ew-bg:#eff7f6;--ew-card:#fff;--ew-line:#c9e3df;--ew-ink:#193b39;--ew-muted:#607a77;--ew-main:#16806f;overflow:hidden;border:1px solid var(--ew-line);border-radius:18px;background:var(--ew-bg);padding:17px;color:var(--ew-ink);box-shadow:0 8px 24px #145b500d}.dark .ew{--ew-bg:#102320;--ew-card:#15312d;--ew-line:#315a53;--ew-ink:#ecfffa;--ew-muted:#afd0c8;--ew-main:#4bdfbe;box-shadow:0 12px 34px #0004}.ew-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.ew-head>div{display:flex;align-items:center;gap:10px}.ew-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#43c6ad,#147263);box-shadow:0 0 0 4px #2aaf971c}.ew-head h3{margin:0;font-size:14px;font-weight:850;color:var(--ew-ink)}.ew-head p{margin:1px 0 0;font-size:10px;color:var(--ew-muted)}.ew-head a{font-size:11px;font-weight:800;color:var(--ew-main)}.ew-head a b{font-size:14px}.ew-alert{display:flex;align-items:center;gap:11px;margin-bottom:13px;border:1px solid #e5b45f;border-radius:13px;background:#fff5df;padding:11px 14px;color:#8b5605}.dark .ew-alert{border-color:#795b2b;background:#3d2d14;color:#ffd48c}.ew-alert>svg{width:23px;flex:none}.ew-alert strong,.ew-alert span{display:block}.ew-alert strong{font-size:12px;font-weight:850}.ew-alert span{margin-top:2px;font-size:9px}.ew-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.ew-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--ew-line);border-radius:16px;background:var(--ew-card);padding:17px 18px;color:inherit;transition:.18s}.ew-card:hover{transform:translateY(-2px);border-color:#55a99b;box-shadow:0 9px 22px #145b5018}.dark .ew-card:hover{border-color:#4b9185;box-shadow:0 9px 24px #0005}.ew-card strong{font-size:28px;line-height:1;color:var(--ew-main);font-weight:900}.ew-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--ew-ink)}.ew-card small{margin-top:5px;font-size:9px;font-weight:650;color:var(--ew-muted)}.ew-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#249f8b16;color:#16806f}.dark .ew-icon{background:#4bdfbe1c;color:#65eccd}.ew-card.pending{border-color:#e5bd78}.ew-card.pending strong{color:#d4890d}.ew-card.pending .ew-icon{background:#e39a2418;color:#d98a11}.ew-card.approved strong{color:#258264}.ew-card.approved .ew-icon{background:#29936d16;color:#258264}.ew-card.rejected strong{color:#d94d58}.ew-card.rejected .ew-icon{background:#df566118;color:#d94d58}.dark .ew-card.pending{border-color:#735a32}.dark .ew-card.approved strong{color:#62d8ae}.dark .ew-card.rejected strong{color:#ff8992}@media(max-width:900px){.ew-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.ew{padding:13px}.ew-head{align-items:flex-start}.ew-grid{grid-template-columns:1fr}.ew-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
