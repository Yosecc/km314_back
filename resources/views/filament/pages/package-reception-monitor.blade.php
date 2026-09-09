<x-filament-panels::page>
    <div class="package-monitor" wire:poll.30s x-data="{ tutorialOpen: @entangle('showTutorial').live, tutorialStep: 1, tutorialDirection: 1, closeTutorial() { this.tutorialOpen = false; this.tutorialStep = 1; this.tutorialDirection = 1; this.$wire.markTutorialSeen(); } }">
        @php($data = $this->monitorData)
        <div class="pm-hero">
            <div><span class="pm-eyebrow">Recepción en tiempo real</span><h2>Seguimiento de paquetes</h2><p>Última actualización {{ $data['updated_at'] }}</p></div>
            <div class="pm-hero-art" aria-hidden="true"><div class="pm-box"><i></i></div></div>
            <div class="pm-hero-actions">
                <button type="button" class="pm-help" @click="tutorialDirection = 1; tutorialStep = 1; tutorialOpen = true"><x-heroicon-o-question-mark-circle class="h-5 w-5" /> Cómo funciona</button>
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
                        <div class="pm-operation-actions">
                            @if($record['can_receive'])
                                {{ ($this->receiveAction)(['reception' => $record['id']]) }}
                            @endif
                            @if($record['can_deliver'])
                                {{ ($this->deliverAction)(['reception' => $record['id']]) }}
                            @endif
                            @if($record['can_cancel'])
                                {{ ($this->cancelAction)(['reception' => $record['id']]) }}
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="pm-empty"><span>✓</span><h3>No hay recepciones para mostrar</h3><p>Pruebe otro filtro o una búsqueda diferente.</p></div>
            @endforelse
        </div>

        <div x-cloak x-show="tutorialOpen" x-transition.opacity class="pm-tutorial-backdrop" role="dialog" aria-modal="true" aria-labelledby="pm-tutorial-title" @click="closeTutorial()" @keydown.escape.window="if (tutorialOpen) closeTutorial()">
            <section class="pm-tutorial" @click.stop>
                <button type="button" class="pm-tutorial-close" aria-label="Cerrar tutorial" @click="closeTutorial()"><x-heroicon-o-x-mark /></button>
                <div class="pm-tutorial-progress" aria-label="Progreso del tutorial">
                    <template x-for="number in 3" :key="number"><button type="button" :class="{ active: tutorialStep === number, complete: tutorialStep > number }" @click="tutorialDirection = number > tutorialStep ? 1 : -1; tutorialStep = number"><span x-text="number"></span></button></template>
                </div>

                <div class="pm-tutorial-stage" :style="`--tutorial-direction: ${tutorialDirection}`">
                <div x-show="tutorialStep === 1" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" class="pm-tutorial-step">
                    <img src="{{ asset('images/package-reception-tutorial/welcome.webp') }}" alt="Una persona de correo entrega un paquete en la recepción del barrio">
                    <div class="pm-tutorial-copy">
                        <span class="pm-step-label">Paso 1 de 3 · Una recepción preparada</span>
                        <h2 id="pm-tutorial-title">Recibimos tus paquetes con la información correcta</h2>
                        <p>Este módulo permite que los propietarios avisen con anticipación qué envío esperan. Así, cuando el correo llegue, el personal de recepción tendrá los datos necesarios para identificarlo y recibirlo sin llamadas ni demoras.</p>
                        <ul><li>Recepción sabe para quién y para qué lote es el paquete.</li><li>Los códigos y observaciones importantes quedan disponibles de forma segura.</li></ul>
                    </div>
                </div>

                <div x-show="tutorialStep === 2" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" class="pm-tutorial-step">
                    <img src="{{ asset('images/package-reception-tutorial/create-request.webp') }}" alt="Una propietaria registra en el sistema la llegada de un paquete">
                    <div class="pm-tutorial-copy">
                        <span class="pm-step-label">Paso 2 de 3 · Registrá el aviso</span>
                        <h2>Creá una recepción antes de que llegue el correo</h2>
                        <p>Presioná <strong>Nueva recepción</strong> e indicá la empresa de correo, la fecha y el rango horario estimado. También podés agregar seguimiento, código de entrega, cantidad de bultos, imágenes y observaciones útiles.</p>
                        <ul><li>Solo el correo y la ventana de llegada son obligatorios.</li><li>Mientras siga “En espera”, podés corregir la solicitud o cancelarla.</li></ul>
                    </div>
                </div>

                <div x-show="tutorialStep === 3" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" class="pm-tutorial-step">
                    <img src="{{ asset('images/package-reception-tutorial/track-package.webp') }}" alt="Un monitor muestra el seguimiento del paquete y avisa al propietario">
                    <div class="pm-tutorial-copy">
                        <span class="pm-step-label">Paso 3 de 3 · Seguí cada etapa</span>
                        <h2>Consultá el monitor y enterate cuando llegue</h2>
                        <p>El monitor reúne todos los envíos y muestra claramente si están en espera, fueron recibidos, entregados o cancelados. Cuando recepción registra un cambio de estado, el propietario recibe una notificación.</p>
                        <ul><li>Las alertas señalan envíos fuera de horario o paquetes pendientes de retiro.</li><li>Al retirarlo, queda registrado quién lo entregó, cuándo y a quién.</li></ul>
                    </div>
                                    </div>
                </div>

                <footer class="pm-tutorial-footer">
                    <button type="button" class="pm-tutorial-skip" @click="closeTutorial()" x-text="tutorialStep === 3 ? 'Cerrar' : 'Omitir tutorial'"></button>
                    <div>
                        <button type="button" class="pm-tutorial-prev" x-show="tutorialStep > 1" @click="tutorialDirection = -1; tutorialStep--"><x-heroicon-o-arrow-left /> Anterior</button>
                        <button type="button" class="pm-tutorial-next" x-show="tutorialStep < 3" @click="tutorialDirection = 1; tutorialStep++">Siguiente <x-heroicon-o-arrow-right /></button>
                        @if($data['can_create'])
                            <a href="{{ $data['create_url'] }}" class="pm-tutorial-start" x-show="tutorialStep === 3" @click="$wire.markTutorialSeen()">Crear una recepción <x-heroicon-o-plus /></a>
                        @else
                            <button type="button" class="pm-tutorial-start" x-show="tutorialStep === 3" @click="closeTutorial()">Entendido <x-heroicon-o-check /></button>
                        @endif
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <style>
        .package-monitor{--ink:#15231f;--green:#176b53;--mint:#eaf6f1;--line:#dce8e3;color:var(--ink)}
        .dark .package-monitor{--ink:#eef8f4;--green:#5bd5aa;--mint:#203e35;--line:#355148;color:#eef8f4}
        .pm-hero{position:relative;overflow:hidden;background:linear-gradient(125deg,#123f35,#176b53 65%,#25916f);border-radius:22px;padding:28px 30px;color:white;display:flex;align-items:center;justify-content:space-between;gap:22px;box-shadow:0 16px 40px #154f3d25}.pm-hero>div:first-child,.pm-hero-actions{position:relative;z-index:2}.pm-eyebrow{text-transform:uppercase;letter-spacing:.13em;font-size:11px;font-weight:800;color:#aee8d3}.pm-hero h2{font-size:28px;font-weight:800;margin:4px 0}.pm-hero p{opacity:.75;font-size:13px}.pm-hero-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.pm-hero-actions a{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:11px;font-size:12px;font-weight:800}.pm-create{background:#fff;color:#155b48;box-shadow:0 5px 18px #0b322555}.pm-list{color:#fff;border:1px solid #ffffff65;background:#ffffff12}.pm-hero-art{position:absolute;z-index:1;right:33%;top:4px;width:150px;height:120px;opacity:.18;transform:rotate(7deg)}.pm-box{position:absolute;left:23px;top:21px;width:92px;height:72px;border:2px solid white;border-radius:8px;transform:skewY(-5deg);box-shadow:0 13px 24px #082d2244}.pm-box:before{content:'';position:absolute;left:-2px;right:-2px;top:19px;border-top:2px solid white}.pm-box:after{content:'';position:absolute;left:45px;top:-2px;bottom:-2px;border-left:2px solid white}.pm-box i{position:absolute;width:25px;height:13px;left:10px;top:34px;border:2px solid white;border-radius:3px}
        .pm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:18px 0}.pm-stats button{text-align:left;background:white;border:1px solid var(--line);border-radius:16px;padding:17px 19px;transition:.18s;color:var(--ink)}.dark .pm-stats button,.dark .pm-card,.dark .pm-toolbar{background:#16251f;border-color:#355148}.pm-stats button:hover{transform:translateY(-2px);box-shadow:0 8px 20px #173d3020}.pm-stats b{display:block;font-size:27px}.pm-stats span{font-size:12px;color:#66756f;font-weight:650}.dark .pm-stats span{color:#a9beb5}.pm-stats .warning b{color:#d98b10}.pm-stats .danger b{color:#ef5656}
        .pm-toolbar{background:white;border:1px solid var(--line);border-radius:15px;padding:10px;display:flex;gap:14px;justify-content:space-between;align-items:center}.pm-tabs{display:flex;gap:4px;flex-wrap:wrap}.pm-tabs button{padding:8px 11px;border-radius:9px;font-size:12px;font-weight:700;color:#63716c}.dark .pm-tabs button{color:#abc0b7}.pm-tabs button.active{background:var(--mint);color:var(--green)}.dark .pm-tabs button.active{background:#e7f7f0;color:#126149}.pm-toolbar input{border:1px solid var(--line);border-radius:10px;padding:9px 12px;width:330px;font-size:13px;background:transparent;color:var(--ink)}.dark .pm-toolbar input::placeholder{color:#8ca39a;opacity:1}
        .pm-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-top:16px}.pm-card{display:block;background:white;border:1px solid var(--line);border-radius:17px;padding:18px;color:inherit;transition:.18s;position:relative;overflow:hidden}.pm-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px #153d3020;border-color:#a8cdbf}.dark .pm-card:hover{border-color:#5b8d7c;box-shadow:0 12px 28px #00000055}.pm-card.has-alert:before{content:'';position:absolute;inset:0 auto 0 0;width:4px;background:#e29220}.pm-card-top{display:flex;justify-content:space-between;gap:8px}.pm-ref{font-size:11px;letter-spacing:.08em;color:#74827d;font-weight:800}.dark .pm-ref{color:#a8bdb4}.pm-badge{font-size:10px;font-weight:800;border-radius:999px;padding:4px 8px;background:#eee}.pm-badge.expected{background:#fff4d8;color:#9a6309}.pm-badge.received{background:#e6f1ff;color:#2362a0}.pm-badge.delivered{background:#e4f7ee;color:#16714f}.pm-badge.cancelled{background:#feecec;color:#a43131}.pm-card h3{font-size:19px;font-weight:800;margin:13px 0 2px;color:var(--ink)}.pm-owner{font-size:13px;color:#617069}.dark .pm-owner{color:#b1c5bd}.pm-owner span{color:#9faca7}.pm-window{background:#f4f8f6;border-radius:10px;padding:10px 12px;margin-top:14px}.dark .pm-window{background:#0d1814;border:1px solid #263d34}.pm-window span{display:block;font-size:10px;color:#718079;text-transform:uppercase;letter-spacing:.08em}.dark .pm-window span{color:#8fb0a3}.pm-window strong{font-size:13px;color:var(--ink)}.pm-tracking{font-size:11px;color:#718079;margin-top:10px}.dark .pm-tracking{color:#9db4aa}.pm-alert{background:#fff3dd;color:#9b5f08;border-radius:9px;padding:8px 10px;font-size:12px;font-weight:750;margin-top:10px}.dark .pm-alert{background:#4a3517;color:#ffd58b;border:1px solid #745426}.pm-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px}.pm-open{font-size:12px;font-weight:750;color:var(--green)}.pm-empty{grid-column:1/-1;text-align:center;padding:60px 20px;color:#718079}.dark .pm-empty{color:#a9beb5}.pm-empty span{font-size:35px;color:#2b9b72}.pm-empty h3{font-weight:800;color:var(--ink);margin:8px}
        .pm-operation-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}
        .pm-help{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid #ffffff65;border-radius:11px;background:#ffffff12;color:#fff;font-size:12px;font-weight:800;transition:.18s}.pm-help:hover{background:#ffffff25}.pm-help svg{width:18px}
        [x-cloak]{display:none!important}.pm-tutorial-backdrop{position:fixed;z-index:100000;inset:0;display:grid;place-items:center;background:#06110de8;padding:22px;backdrop-filter:blur(7px)}.pm-tutorial{position:relative;width:min(940px,100%);max-height:calc(100vh - 44px);overflow:auto;border:1px solid #d6e5df;border-radius:24px;background:#fff;color:#17251f;box-shadow:0 30px 90px #0008}.dark .pm-tutorial{border-color:#355148;background:#122019;color:#eef8f4}.pm-tutorial-close{position:absolute;z-index:3;right:15px;top:15px;display:grid;place-items:center;width:36px;height:36px;border:1px solid #ffffff88;border-radius:11px;background:#102c25b8;color:#fff;backdrop-filter:blur(5px)}.pm-tutorial-close svg{width:20px}.pm-tutorial-progress{position:absolute;z-index:3;left:24px;top:22px;display:flex;gap:7px}.pm-tutorial-progress button{display:grid;place-items:center;width:27px;height:27px;border:1px solid #ffffff99;border-radius:99px;background:#173f35a8;color:#dcebe5;font-size:10px;font-weight:900;backdrop-filter:blur(5px);transition:width .3s ease,background-color .3s ease,color .3s ease}.pm-tutorial-progress button.active{width:40px;background:#fff;color:#176b53}.pm-tutorial-progress button.complete{background:#2a9d76;color:#fff}.pm-tutorial-stage{display:grid;overflow:hidden}.pm-tutorial-step{grid-area:1/1;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(330px,.92fr);min-height:475px;will-change:transform,opacity}.pm-step-enter,.pm-step-leave{transition:opacity .36s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)}.pm-step-enter-start{opacity:0;transform:translateX(calc(var(--tutorial-direction) * 34px)) scale(.985)}.pm-step-enter-end,.pm-step-leave-start{opacity:1;transform:translateX(0) scale(1)}.pm-step-leave-end{opacity:0;transform:translateX(calc(var(--tutorial-direction) * -24px)) scale(.99)}.pm-tutorial-step>img{width:100%;height:100%;min-height:475px;object-fit:cover}.pm-tutorial-copy{display:flex;flex-direction:column;justify-content:center;padding:56px 42px 38px}.pm-step-label{font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#228062}.pm-tutorial-copy h2{margin:9px 0 13px;font-size:26px;line-height:1.12;font-weight:900;color:inherit}.pm-tutorial-copy p{font-size:13px;line-height:1.65;color:#53665e}.dark .pm-tutorial-copy p{color:#b4c8c0}.pm-tutorial-copy ul{display:grid;gap:8px;margin:17px 0 0;padding:0;list-style:none}.pm-tutorial-copy li{position:relative;padding-left:22px;font-size:11px;line-height:1.45;color:#52645d}.dark .pm-tutorial-copy li{color:#b4c8c0}.pm-tutorial-copy li:before{content:'✓';position:absolute;left:0;top:-1px;display:grid;place-items:center;width:16px;height:16px;border-radius:99px;background:#e5f5ee;color:#187052;font-size:10px;font-weight:900}.dark .pm-tutorial-copy li:before{background:#24473b;color:#6fe0b8}.pm-tutorial-footer{position:sticky;z-index:2;bottom:0;display:flex;align-items:center;justify-content:space-between;gap:14px;border-top:1px solid #e2ebe7;background:#fffffff2;padding:15px 22px;backdrop-filter:blur(7px)}.dark .pm-tutorial-footer{border-color:#30483f;background:#122019f2}.pm-tutorial-footer>div{display:flex;align-items:center;gap:8px}.pm-tutorial-footer button,.pm-tutorial-footer a{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:9px 13px;font-size:11px;font-weight:850}.pm-tutorial-footer svg{width:16px}.pm-tutorial-skip{color:#74827c}.dark .pm-tutorial-skip{color:#a9bbb4}.pm-tutorial-prev{border:1px solid #cfddd7;color:#456158}.dark .pm-tutorial-prev{border-color:#3d574d;color:#c7d8d1}.pm-tutorial-next,.pm-tutorial-start{background:#197457;color:#fff;box-shadow:0 5px 15px #176b5330}.pm-tutorial-next:hover,.pm-tutorial-start:hover{background:#125f46}@media(prefers-reduced-motion:reduce){.pm-step-enter,.pm-step-leave,.pm-tutorial-progress button{transition-duration:.01ms!important}}
        @media(max-width:1024px){.pm-grid{grid-template-columns:repeat(2,1fr)}.pm-toolbar{align-items:stretch;flex-direction:column}.pm-toolbar input{width:100%}.pm-hero-art{right:27%}}@media(max-width:720px){.pm-tutorial-backdrop{padding:10px}.pm-tutorial{max-height:calc(100vh - 20px);border-radius:18px}.pm-tutorial-step{display:block;min-height:0}.pm-tutorial-step>img{height:220px;min-height:0}.pm-tutorial-copy{padding:25px 21px 22px}.pm-tutorial-copy h2{font-size:22px}.pm-tutorial-progress{left:15px;top:14px}.pm-tutorial-close{right:12px;top:12px}.pm-tutorial-footer{align-items:stretch;flex-direction:column;padding:12px 16px}.pm-tutorial-footer>div{justify-content:flex-end}}@media(max-width:640px){.pm-hero{align-items:flex-start;gap:18px;flex-direction:column}.pm-hero-actions{justify-content:flex-start}.pm-hero-art{right:-12px;top:0}.pm-stats{grid-template-columns:repeat(2,1fr)}.pm-grid{grid-template-columns:1fr}.pm-hero h2{font-size:23px}.pm-actions{align-items:flex-start;flex-direction:column}.pm-operation-actions{justify-content:flex-start}}
    </style>
</x-filament-panels::page>
