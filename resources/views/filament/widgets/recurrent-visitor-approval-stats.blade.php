<x-filament-widgets::widget>
    <section class="rvw" wire:poll.30s>
        <header class="rvw-head">
            <div><span></span><div><h3>Visitantes Recurrentes</h3><p>Solicitudes registradas y estado de aprobación</p></div></div>
            @if($canManage)<a href="{{ $resourceUrl }}">Gestionar visitantes <b>→</b></a>@endif
        </header>

        @if($hasPending)
            <div class="rvw-alert"><x-heroicon-o-bell-alert /><div><strong>Hay visitantes pendientes por aprobar</strong><span>Revise las solicitudes para aprobarlas o rechazarlas.</span></div></div>
        @endif

        <div class="rvw-grid">
            @foreach($cards as $card)
                @php($cardUrl = $card['status'] ? $resourceUrl.'?'.http_build_query(['tableFilters'=>['status'=>['value'=>$card['status']]]]) : $resourceUrl)
                @if($canManage)<a href="{{ $cardUrl }}" class="rvw-card {{ $card['tone'] }}">@else<div class="rvw-card {{ $card['tone'] }}">@endif
                    <div class="rvw-icon"><x-dynamic-component :component="$card['icon']" /></div>
                    <strong>{{ $card['value'] }}</strong><span>{{ $card['label'] }}</span>
                    @if(isset($card['detail']))<small>{{ $card['detail'] }}</small>@endif
                @if($canManage)</a>@else</div>@endif
            @endforeach
        </div>
    </section>

    <style>
        .rvw{--rvw-bg:#f5f2fb;--rvw-card:#fff;--rvw-line:#ddd5ed;--rvw-ink:#29213c;--rvw-muted:#766d88;--rvw-main:#7253a7;overflow:hidden;border:1px solid var(--rvw-line);border-radius:18px;background:var(--rvw-bg);padding:17px;color:var(--rvw-ink);box-shadow:0 8px 24px #39265d0d}.dark .rvw{--rvw-bg:#171321;--rvw-card:#211a2f;--rvw-line:#493c61;--rvw-ink:#f6f1ff;--rvw-muted:#bdb0d1;--rvw-main:#b99aee;box-shadow:0 12px 34px #0004}.rvw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.rvw-head>div{display:flex;align-items:center;gap:10px}.rvw-head>div>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#a47bde,#68439f);box-shadow:0 0 0 4px #8055bb1c}.rvw-head h3{margin:0;font-size:14px;font-weight:850;color:var(--rvw-ink)}.rvw-head p{margin:1px 0 0;font-size:10px;color:var(--rvw-muted)}.rvw-head a{font-size:11px;font-weight:800;color:var(--rvw-main)}.rvw-head a b{font-size:14px}.rvw-alert{display:flex;align-items:center;gap:11px;margin-bottom:13px;border:1px solid #e5b45f;border-radius:13px;background:#fff5df;padding:11px 14px;color:#8b5605}.dark .rvw-alert{border-color:#795b2b;background:#3d2d14;color:#ffd48c}.rvw-alert>svg{width:23px;flex:none}.rvw-alert strong,.rvw-alert span{display:block}.rvw-alert strong{font-size:12px;font-weight:850}.rvw-alert span{margin-top:2px;font-size:9px}.rvw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.rvw-card{position:relative;display:flex;min-height:116px;flex-direction:column;justify-content:center;border:1px solid var(--rvw-line);border-radius:16px;background:var(--rvw-card);padding:17px 18px;color:inherit;transition:.18s}.rvw-card:hover{transform:translateY(-2px);border-color:#a890cc;box-shadow:0 9px 22px #39265d18}.dark .rvw-card:hover{border-color:#79649c;box-shadow:0 9px 24px #0005}.rvw-card strong{font-size:28px;line-height:1;color:var(--rvw-main);font-weight:900}.rvw-card>span{margin-top:8px;font-size:12px;font-weight:800;color:var(--rvw-ink)}.rvw-card small{margin-top:5px;font-size:9px;font-weight:650;color:var(--rvw-muted)}.rvw-icon{position:absolute;right:15px;top:14px;width:29px;height:29px;padding:6px;border-radius:9px;background:#8055bb16;color:#7650a9}.dark .rvw-icon{background:#b99aee1c;color:#c5a8f4}.rvw-card.pending{border-color:#e5bd78}.rvw-card.pending strong{color:#d4890d}.rvw-card.pending .rvw-icon{background:#e39a2418;color:#d98a11}.rvw-card.approved strong{color:#258264}.rvw-card.approved .rvw-icon{background:#29936d16;color:#258264}.rvw-card.rejected strong{color:#d94d58}.rvw-card.rejected .rvw-icon{background:#df566118;color:#d94d58}.dark .rvw-card.pending{border-color:#735a32}.dark .rvw-card.approved strong{color:#62d8ae}.dark .rvw-card.rejected strong{color:#ff8992}
        @media(max-width:900px){.rvw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.rvw{padding:13px}.rvw-head{align-items:flex-start}.rvw-grid{grid-template-columns:1fr}.rvw-card{min-height:105px}}
    </style>
</x-filament-widgets::widget>
