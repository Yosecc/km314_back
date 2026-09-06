<x-filament-panels::page>
    <div class="package-monitor" wire:poll.30s>
        @php($data = $this->monitorData)
        <div class="pm-hero">
            <div><span class="pm-eyebrow">Recepción en tiempo real</span><h2>Seguimiento de paquetes</h2><p>Última actualización {{ $data['updated_at'] }}</p></div>
            <div class="pm-hero-art" aria-hidden="true"><div class="pm-box"><i></i></div></div>
            <div class="pm-hero-actions">
                @if($data['can_create'])<a href="{{ $data['create_url'] }}" class="pm-create"><x-heroicon-o-plus class="h-5 w-5" /> Nueva recepción</a>@endif
                @if($data['can_manage'])<a href="{{ $data['operations_url'] }}" class="pm-list">Gestionar recepciones</a>@endif
            </div>
        </div>
        <div class="pm-stats">
            <button wire:click="setStatus('expected')"><b>{{ $data['stats']['expected'] }}</b><span>Esperados</span></button>
            <button wire:click="setStatus('received')"><b>{{ $data['stats']['received'] }}</b><span>En recepción</span></button>
            <button wire:click="setStatus('alerts')" class="warning"><b>{{ $data['stats']['overdue'] }}</b><span>No llegaron</span></button>
            <button wire:click="setStatus('alerts')" class="danger"><b>{{ $data['stats']['pickup'] }}</b><span>Retiro demorado</span></button>
        </div>
        <div class="pm-toolbar">
            <div class="pm-tabs">
                @foreach(['active'=>'Activos','expected'=>'Esperados','received'=>'Recibidos','delivered'=>'Entregados','cancelled'=>'Cancelados','alerts'=>'Alertas'] as $key=>$label)
                    <button wire:click="setStatus('{{ $key }}')" @class(['active'=>$status===$key])>{{ $label }}</button>
                @endforeach
            </div>
            <input wire:model.live.debounce.350ms="search" placeholder="Buscar correo, referencia, propietario, lote…">
        </div>
        <div class="pm-grid">
            @forelse($data['records'] as $record)
                <article class="pm-card {{ $record['alert'] ? 'has-alert' : '' }}">
                    <div class="pm-card-top"><span class="pm-ref">{{ $record['reference'] }}</span><span class="pm-badge {{ $record['status'] }}">{{ $record['status_label'] }}</span></div>
                    <h3>{{ $record['courier'] }}</h3>
                    <p class="pm-owner">{{ $record['owner'] }} <span>·</span> {{ $record['lot'] }}</p>
                    <div class="pm-window"><span>Ventana esperada</span><strong>{{ $record['from'] }} — {{ $record['until'] }}</strong></div>
                    @if($record['tracking'])<p class="pm-tracking">Seguimiento: {{ $record['tracking'] }}</p>@endif
                    @if($record['alert'])<div class="pm-alert">⚠ {{ $record['alert'] }}</div>@endif
                    <div class="pm-actions">
                        <a href="{{ $record['url'] }}" class="pm-open">Ver detalle →</a>
                        @if($record['can_receive'])
                            {{ ($this->receiveAction)(['reception' => $record['id']]) }}
                        @endif
                    </div>
                </article>
            @empty
                <div class="pm-empty"><span>✓</span><h3>No hay recepciones para mostrar</h3><p>Pruebe otro filtro o una búsqueda diferente.</p></div>
            @endforelse
        </div>
    </div>
    <style>
        .package-monitor{--ink:#15231f;--green:#176b53;--mint:#eaf6f1;--line:#dce8e3;color:var(--ink)}
        .dark .package-monitor{--ink:#eef8f4;--green:#5bd5aa;--mint:#203e35;--line:#355148;color:#eef8f4}
        .pm-hero{position:relative;overflow:hidden;background:linear-gradient(125deg,#123f35,#176b53 65%,#25916f);border-radius:22px;padding:28px 30px;color:white;display:flex;align-items:center;justify-content:space-between;gap:22px;box-shadow:0 16px 40px #154f3d25}.pm-hero>div:first-child,.pm-hero-actions{position:relative;z-index:2}.pm-eyebrow{text-transform:uppercase;letter-spacing:.13em;font-size:11px;font-weight:800;color:#aee8d3}.pm-hero h2{font-size:28px;font-weight:800;margin:4px 0}.pm-hero p{opacity:.75;font-size:13px}.pm-hero-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.pm-hero-actions a{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:11px;font-size:12px;font-weight:800}.pm-create{background:#fff;color:#155b48;box-shadow:0 5px 18px #0b322555}.pm-list{color:#fff;border:1px solid #ffffff65;background:#ffffff12}.pm-hero-art{position:absolute;z-index:1;right:33%;top:4px;width:150px;height:120px;opacity:.18;transform:rotate(7deg)}.pm-box{position:absolute;left:23px;top:21px;width:92px;height:72px;border:2px solid white;border-radius:8px;transform:skewY(-5deg);box-shadow:0 13px 24px #082d2244}.pm-box:before{content:'';position:absolute;left:-2px;right:-2px;top:19px;border-top:2px solid white}.pm-box:after{content:'';position:absolute;left:45px;top:-2px;bottom:-2px;border-left:2px solid white}.pm-box i{position:absolute;width:25px;height:13px;left:10px;top:34px;border:2px solid white;border-radius:3px}
        .pm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:18px 0}.pm-stats button{text-align:left;background:white;border:1px solid var(--line);border-radius:16px;padding:17px 19px;transition:.18s;color:var(--ink)}.dark .pm-stats button,.dark .pm-card,.dark .pm-toolbar{background:#16251f;border-color:#355148}.pm-stats button:hover{transform:translateY(-2px);box-shadow:0 8px 20px #173d3020}.pm-stats b{display:block;font-size:27px}.pm-stats span{font-size:12px;color:#66756f;font-weight:650}.dark .pm-stats span{color:#a9beb5}.pm-stats .warning b{color:#d98b10}.pm-stats .danger b{color:#ef5656}
        .pm-toolbar{background:white;border:1px solid var(--line);border-radius:15px;padding:10px;display:flex;gap:14px;justify-content:space-between;align-items:center}.pm-tabs{display:flex;gap:4px;flex-wrap:wrap}.pm-tabs button{padding:8px 11px;border-radius:9px;font-size:12px;font-weight:700;color:#63716c}.dark .pm-tabs button{color:#abc0b7}.pm-tabs button.active{background:var(--mint);color:var(--green)}.dark .pm-tabs button.active{background:#e7f7f0;color:#126149}.pm-toolbar input{border:1px solid var(--line);border-radius:10px;padding:9px 12px;width:330px;font-size:13px;background:transparent;color:var(--ink)}.dark .pm-toolbar input::placeholder{color:#8ca39a;opacity:1}
        .pm-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-top:16px}.pm-card{display:block;background:white;border:1px solid var(--line);border-radius:17px;padding:18px;color:inherit;transition:.18s;position:relative;overflow:hidden}.pm-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px #153d3020;border-color:#a8cdbf}.dark .pm-card:hover{border-color:#5b8d7c;box-shadow:0 12px 28px #00000055}.pm-card.has-alert:before{content:'';position:absolute;inset:0 auto 0 0;width:4px;background:#e29220}.pm-card-top{display:flex;justify-content:space-between;gap:8px}.pm-ref{font-size:11px;letter-spacing:.08em;color:#74827d;font-weight:800}.dark .pm-ref{color:#a8bdb4}.pm-badge{font-size:10px;font-weight:800;border-radius:999px;padding:4px 8px;background:#eee}.pm-badge.expected{background:#fff4d8;color:#9a6309}.pm-badge.received{background:#e6f1ff;color:#2362a0}.pm-badge.delivered{background:#e4f7ee;color:#16714f}.pm-badge.cancelled{background:#feecec;color:#a43131}.pm-card h3{font-size:19px;font-weight:800;margin:13px 0 2px;color:var(--ink)}.pm-owner{font-size:13px;color:#617069}.dark .pm-owner{color:#b1c5bd}.pm-owner span{color:#9faca7}.pm-window{background:#f4f8f6;border-radius:10px;padding:10px 12px;margin-top:14px}.dark .pm-window{background:#0d1814;border:1px solid #263d34}.pm-window span{display:block;font-size:10px;color:#718079;text-transform:uppercase;letter-spacing:.08em}.dark .pm-window span{color:#8fb0a3}.pm-window strong{font-size:13px;color:var(--ink)}.pm-tracking{font-size:11px;color:#718079;margin-top:10px}.dark .pm-tracking{color:#9db4aa}.pm-alert{background:#fff3dd;color:#9b5f08;border-radius:9px;padding:8px 10px;font-size:12px;font-weight:750;margin-top:10px}.dark .pm-alert{background:#4a3517;color:#ffd58b;border:1px solid #745426}.pm-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px}.pm-open{font-size:12px;font-weight:750;color:var(--green)}.pm-empty{grid-column:1/-1;text-align:center;padding:60px 20px;color:#718079}.dark .pm-empty{color:#a9beb5}.pm-empty span{font-size:35px;color:#2b9b72}.pm-empty h3{font-weight:800;color:var(--ink);margin:8px}
        @media(max-width:1024px){.pm-grid{grid-template-columns:repeat(2,1fr)}.pm-toolbar{align-items:stretch;flex-direction:column}.pm-toolbar input{width:100%}.pm-hero-art{right:27%}}@media(max-width:640px){.pm-hero{align-items:flex-start;gap:18px;flex-direction:column}.pm-hero-actions{justify-content:flex-start}.pm-hero-art{right:-12px;top:0}.pm-stats{grid-template-columns:repeat(2,1fr)}.pm-grid{grid-template-columns:1fr}.pm-hero h2{font-size:23px}}
    </style>
</x-filament-panels::page>
