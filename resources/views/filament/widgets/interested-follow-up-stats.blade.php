<x-filament-widgets::widget>
    <section class="ifw" wire:poll.60s>
        <header><div><i></i><span><strong>Seguimiento de interesados</strong><small>Tu agenda comercial</small></span></div>@if($canOpenMonitor)<a href="{{ $monitorUrl }}">Abrir monitor →</a>@endif</header>
        <div class="ifw-grid">
            @foreach($cards as $card)
                @if($canOpenMonitor)<a href="{{ $monitorUrl }}?timingFilter={{ $card['filter'] }}" class="{{ $card['tone'] }}">@else<div class="{{ $card['tone'] }}">@endif
                    <b>{{ $card['value'] }}</b><span>{{ $card['label'] }}</span>
                @if($canOpenMonitor)</a>@else</div>@endif
            @endforeach
        </div>
    </section>
    <style>
        .ifw{--bg:#f7f3fb;--card:#fff;--line:#e3d8ef;--ink:#2b2038;--muted:#756780;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:var(--bg);padding:16px;color:var(--ink)}.dark .ifw{--bg:#17121f;--card:#231b2d;--line:#4b3b5d;--ink:#f8f1ff;--muted:#b9a9c7}.ifw header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}.ifw header>div{display:flex;align-items:center;gap:9px}.ifw header i{width:9px;height:29px;border-radius:99px;background:linear-gradient(#a671d0,#67408b)}.ifw header span{display:flex;flex-direction:column}.ifw header strong{font-size:13px;font-weight:900}.ifw header small{font-size:9px;color:var(--muted)}.ifw header a{font-size:10px;font-weight:850;color:#74459c}.dark .ifw header a{color:#c495ed}.ifw-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:11px}.ifw-grid>a,.ifw-grid>div{display:flex;min-height:88px;flex-direction:column;justify-content:center;border:1px solid var(--line);border-radius:14px;background:var(--card);padding:14px;color:inherit}.ifw-grid b{font-size:25px;font-weight:950;color:#71439b}.ifw-grid span{margin-top:4px;font-size:10px;font-weight:800}.ifw-grid .overdue b{color:#d54851}.ifw-grid .today b{color:#d38a1f}.ifw-grid .upcoming b{color:#2b9168}@media(max-width:760px){.ifw-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:430px){.ifw-grid{grid-template-columns:1fr}}
    </style>
</x-filament-widgets::widget>
