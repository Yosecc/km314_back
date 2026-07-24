<x-filament-panels::page>
    @php($monitor = $this->monitorData)

    <div
        class="access-monitor"
        x-data="{ paused: false }"
    >
        <div x-show="! paused" wire:poll.15s="$refresh" aria-hidden="true"></div>

        <section class="monitor-hero">
            <div>
                <div class="live-label">
                    <span class="live-dot"></span>
                    Actividad en vivo
                </div>
                <h2>¿Quién entró y quién salió?</h2>
                <p>Información actualizada a las {{ $monitor['updated_at'] }}.</p>
            </div>

            <button
                type="button"
                class="pause-button"
                x-on:click="paused = ! paused"
                x-bind:class="{ 'is-paused': paused }"
            >
                <svg x-show="! paused" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <svg x-cloak x-show="paused" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 8 6 4-6 4V8Zm11 4a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span x-text="paused ? 'Reanudar' : 'Pausar'"></span>
            </button>
        </section>

        <section class="stats-grid" aria-label="Resumen">
            <article class="stat-card stat-inside">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19a6 6 0 0 0-12 0m9-10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm10 10a6 6 0 0 0-5-5.65M17 5.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div><strong>{{ $monitor['stats']['inside'] }}</strong><span>Personas adentro</span></div>
            </article>
            <article class="stat-card stat-entry">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-5-5 4-4m0 0-4-4m4 4H3"/></svg>
                </div>
                <div><strong>{{ $monitor['stats']['entries'] }}</strong><span>Entradas {{ $monitor['period_label'] }}</span></div>
            </article>
            <article class="stat-card stat-exit">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4m5-5-4-4m0 0 4-4m-4 4h11"/></svg>
                </div>
                <div><strong>{{ $monitor['stats']['exits'] }}</strong><span>Salidas {{ $monitor['period_label'] }}</span></div>
            </article>
            <article class="stat-card stat-alert">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.7 2.4 17.3A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
                </div>
                <div><strong>{{ $monitor['stats']['alerts'] }}</strong><span>Situaciones para revisar</span></div>
            </article>
        </section>

        <section class="monitor-filters">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                <input
                    type="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Buscar por nombre, DNI o lote"
                    aria-label="Buscar movimientos"
                >
            </div>

            <div class="filter-group" aria-label="Período">
                <button type="button" wire:click="setPeriod('today')" @class(['active' => $period === 'today'])>Hoy</button>
                <button type="button" wire:click="setPeriod('24h')" @class(['active' => $period === '24h'])>24 horas</button>
                <button type="button" wire:click="setPeriod('7d')" @class(['active' => $period === '7d'])>7 días</button>
            </div>

            <div class="filter-group movement-filter" aria-label="Tipo de movimiento">
                <button type="button" wire:click="setMovementType('all')" @class(['active' => $movementType === 'all'])>Todos</button>
                <button type="button" wire:click="setMovementType('Entry')" @class(['active' => $movementType === 'Entry'])>Entradas</button>
                <button type="button" wire:click="setMovementType('Exit')" @class(['active' => $movementType === 'Exit'])>Salidas</button>
                <button type="button" wire:click="setMovementType('alerts')" @class(['active', 'alert-active' => $movementType === 'alerts'])>Alertas</button>
            </div>
        </section>

        <div class="monitor-layout">
            <main class="monitor-panel timeline-panel">
                <header class="panel-header">
                    <div>
                        <span class="eyebrow">MOVIMIENTOS</span>
                        <h3>Actividad reciente</h3>
                    </div>
                    <span class="result-count">{{ $monitor['events']->count() }} visibles</span>
                </header>

                <div class="timeline">
                    @forelse($monitor['events'] as $event)
                        <article
                            wire:key="timeline-{{ $event['id'] }}"
                            @class([
                                'timeline-event',
                                'is-entry' => $event['movement'] === 'Entry',
                                'is-exit' => $event['movement'] === 'Exit',
                                'has-alert' => filled($event['alert']) && blank($event['resolved_time']),
                                'is-resolved' => filled($event['resolved_time']),
                            ])
                        >
                            <div class="timeline-marker">
                                <span>
                                    @if($event['movement'] === 'Entry')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-5-5 4-4m0 0-4-4m4 4H3"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4m5-5-4-4m0 0 4-4m-4 4h11"/></svg>
                                    @endif
                                </span>
                            </div>

                            <div class="event-card">
                                <div class="event-time">
                                    <strong>{{ $event['time'] }}</strong>
                                    <span>{{ $event['date'] }}</span>
                                </div>

                                <div class="avatar">{{ $event['initials'] }}</div>

                                <div class="event-person">
                                    <div class="movement-label">
                                        {{ $event['movement'] === 'Entry' ? 'ENTRÓ' : 'SALIÓ' }}
                                    </div>
                                    <h4>{{ $event['name'] }}</h4>
                                    <div class="person-meta">
                                        <span>{{ $event['category'] }}</span>
                                        <span>DNI {{ $event['dni'] }}</span>
                                        <span>Lote {{ $event['lot'] }}</span>
                                    </div>

                                    @if($event['observations'])
                                        <p class="observation">{{ $event['observations'] }}</p>
                                    @endif

                                    @if($event['alert'])
                                        @if($event['resolved_time'])
                                            <div class="event-resolved">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m5 12 4 4L19 6"/></svg>
                                                {{ $event['resolved_message'] }}
                                            </div>
                                        @else
                                            <div class="event-alert">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.7 2.4 17.3A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
                                                {{ $event['alert'] }}
                                            </div>
                                        @endif
                                    @endif

                                    @if($event['alert'] && $event['can_force_exit'])
                                        <button
                                            type="button"
                                            class="force-exit-button"
                                            wire:click="forceExit('{{ $event['model'] }}', {{ $event['model_id'] }})"
                                            wire:confirm="¿Confirmas que deseas registrar una salida forzada para {{ $event['name'] }}?"
                                        >
                                            Forzar salida
                                        </button>
                                    @endif
                                </div>

                                <a class="event-link" href="{{ $event['url'] }}" title="Ver registro" aria-label="Ver registro de {{ $event['name'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-3-3v6m9-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <h4>No hay movimientos para mostrar</h4>
                            <p>Pruebe otro período o cambie los filtros.</p>
                        </div>
                    @endforelse
                </div>
            </main>

            <aside class="monitor-sidebar">
                <section class="monitor-panel inside-panel">
                    <header class="panel-header">
                        <div>
                            <span class="eyebrow">ESTADO ACTUAL</span>
                            <h3>Personas adentro</h3>
                        </div>
                        <span class="inside-count">{{ $monitor['inside']->count() }}</span>
                    </header>

                    <div class="inside-list">
                        @forelse($monitor['inside'] as $person)
                            <article wire:key="inside-{{ $person['identity'] }}" class="inside-person">
                                <div class="avatar">{{ $person['initials'] }}</div>
                                <div class="inside-info">
                                    <h4>{{ $person['name'] }}</h4>
                                    <p>{{ $person['category'] }} · Lote {{ $person['lot'] }}</p>
                                    <span>Entró a las {{ $person['time'] }}</span>
                                </div>
                                <div @class(['duration', 'overdue' => $person['minutes_inside'] >= 720])>
                                    {{ $person['duration'] }}
                                </div>
                            </article>
                        @empty
                            <div class="empty-state compact">
                                <h4>No hay personas adentro</h4>
                                <p>Según el último movimiento registrado.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="monitor-panel alerts-panel">
                    <header class="panel-header">
                        <div>
                            <span class="eyebrow">ATENCIÓN</span>
                            <h3>Situaciones para revisar</h3>
                        </div>
                    </header>

                    <div class="alerts-list">
                        @forelse($monitor['alerts'] as $alert)
                            <article @class(['alert-item', 'is-warning' => $alert['severity'] === 'warning'])>
                                <div class="alert-symbol">!</div>
                                <div>
                                    <strong>{{ $alert['title'] }}</strong>
                                    <p>{{ $alert['detail'] }}</p>
                                    @if($alert['can_force_exit'])
                                        <button
                                            type="button"
                                            class="force-exit-button small"
                                            wire:click="forceExit('{{ $alert['model'] }}', {{ $alert['model_id'] }})"
                                            wire:confirm="¿Confirmas que deseas registrar una salida forzada para {{ $alert['detail'] }}?"
                                        >
                                            Forzar salida
                                        </button>
                                    @endif
                                </div>
                                <span>{{ $alert['time'] }}</span>
                            </article>
                        @empty
                            <div class="all-clear">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                <div><strong>Todo en orden</strong><span>No se detectaron inconsistencias.</span></div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .access-monitor { --am-navy: #11233f; --am-muted: #64748b; --am-line: #e6ebf2; --am-card: #fff; color: var(--am-navy); }
        .monitor-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding: 1.6rem 1.8rem; border-radius: 1.25rem; color: #fff; background: linear-gradient(120deg, #0c2546 0%, #154c79 58%, #0a7c86 130%); box-shadow: 0 18px 45px rgba(15, 43, 75, .18); }
        .monitor-hero::after { content: ""; position: absolute; width: 19rem; height: 19rem; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; right: 8%; top: -10rem; box-shadow: 0 0 0 3rem rgba(255,255,255,.025), 0 0 0 6rem rgba(255,255,255,.02); }
        .monitor-hero > * { position: relative; z-index: 1; }
        .monitor-hero h2 { margin: .35rem 0 .15rem; font-size: clamp(1.45rem, 2.3vw, 2.15rem); line-height: 1.15; font-weight: 800; letter-spacing: -.025em; }
        .monitor-hero p { margin: 0; color: #c8dcf0; font-size: .9rem; }
        .live-label { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; color: #bff6e5; }
        .live-dot { width: .65rem; height: .65rem; border-radius: 50%; background: #35e1a4; box-shadow: 0 0 0 .3rem rgba(53,225,164,.16); animation: access-pulse 1.8s infinite; }
        @keyframes access-pulse { 50% { box-shadow: 0 0 0 .55rem rgba(53,225,164,0); } }
        .pause-button { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.7rem; padding: .65rem 1rem; border: 1px solid rgba(255,255,255,.25); border-radius: .75rem; background: rgba(255,255,255,.11); color: #fff; font-weight: 700; backdrop-filter: blur(8px); transition: .2s ease; }
        .pause-button:hover, .pause-button.is-paused { background: rgba(255,255,255,.22); }
        .pause-button svg { width: 1.2rem; height: 1.2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin: 1rem 0; }
        .stat-card { display: flex; align-items: center; gap: .85rem; min-height: 5.8rem; padding: 1rem; border: 1px solid var(--am-line); border-radius: 1rem; background: var(--am-card); box-shadow: 0 5px 18px rgba(15,35,63,.055); }
        .stat-icon { display: grid; place-items: center; flex: 0 0 2.75rem; height: 2.75rem; border-radius: .8rem; }
        .stat-icon svg { width: 1.35rem; height: 1.35rem; }
        .stat-card strong { display: block; font-size: 1.65rem; line-height: 1; font-weight: 850; letter-spacing: -.04em; }
        .stat-card span { display: block; margin-top: .35rem; color: var(--am-muted); font-size: .77rem; font-weight: 650; line-height: 1.15; }
        .stat-inside .stat-icon { background: #e8f1ff; color: #2563eb; }
        .stat-entry .stat-icon { background: #e5f8ef; color: #078653; }
        .stat-exit .stat-icon { background: #fff1df; color: #cf6611; }
        .stat-alert .stat-icon { background: #fee8e8; color: #dc2626; }
        .monitor-filters { display: flex; align-items: center; gap: .75rem; padding: .8rem; margin-bottom: 1rem; border: 1px solid var(--am-line); border-radius: 1rem; background: var(--am-card); }
        .search-box { display: flex; align-items: center; gap: .6rem; flex: 1 1 18rem; min-width: 14rem; padding: 0 .85rem; height: 2.7rem; border: 1px solid #d8e0ea; border-radius: .75rem; background: #f8fafc; }
        .search-box:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
        .search-box svg { width: 1.1rem; color: #8492a6; }
        .search-box input { width: 100%; border: 0; padding: 0; outline: 0; box-shadow: none !important; background: transparent; color: var(--am-navy); font-size: .86rem; }
        .filter-group { display: flex; gap: .25rem; padding: .25rem; border-radius: .75rem; background: #f1f5f9; white-space: nowrap; }
        .filter-group button { min-height: 2.2rem; padding: .45rem .7rem; border-radius: .55rem; color: #526175; font-size: .76rem; font-weight: 750; transition: .15s ease; }
        .filter-group button:hover { color: #172b48; }
        .filter-group button.active { color: #114a7b; background: #fff; box-shadow: 0 2px 7px rgba(15,35,63,.1); }
        .filter-group button.alert-active { color: #b91c1c; background: #fff1f2; }
        .monitor-layout { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(20rem, .75fr); gap: 1rem; align-items: start; }
        .monitor-sidebar { display: grid; gap: 1rem; position: sticky; top: 1rem; }
        .monitor-panel { overflow: hidden; border: 1px solid var(--am-line); border-radius: 1.15rem; background: var(--am-card); box-shadow: 0 5px 22px rgba(15,35,63,.055); }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.2rem; border-bottom: 1px solid var(--am-line); }
        .panel-header h3 { margin: .15rem 0 0; font-size: 1.05rem; font-weight: 800; letter-spacing: -.015em; }
        .eyebrow { color: #73839a; font-size: .62rem; font-weight: 850; letter-spacing: .14em; }
        .result-count { padding: .35rem .65rem; border-radius: 999px; color: #53647a; background: #f1f5f9; font-size: .7rem; font-weight: 750; }
        .inside-count { display: grid; place-items: center; min-width: 2rem; height: 2rem; border-radius: .65rem; color: #0d6b49; background: #e4f7ee; font-size: .8rem; font-weight: 850; }
        .timeline { max-height: 70rem; overflow-y: auto; padding: .4rem 1.1rem 1.2rem; scrollbar-width: thin; }
        .timeline-event { position: relative; display: grid; grid-template-columns: 2.5rem minmax(0, 1fr); }
        .timeline-event:not(:last-child)::before { content: ""; position: absolute; left: 1.22rem; top: 3.2rem; bottom: -.55rem; width: 2px; background: #e1e7ef; }
        .timeline-marker { position: relative; z-index: 1; padding-top: 1.35rem; }
        .timeline-marker span { display: grid; place-items: center; width: 2.45rem; height: 2.45rem; border: .32rem solid #fff; border-radius: 999px; }
        .timeline-marker svg { width: 1.05rem; height: 1.05rem; }
        .is-entry .timeline-marker span { color: #087b50; background: #dff7eb; box-shadow: 0 0 0 1px #b9ead3; }
        .is-exit .timeline-marker span { color: #c25a0a; background: #fff0dc; box-shadow: 0 0 0 1px #f7d3a7; }
        .has-alert .timeline-marker span { color: #c81e1e; background: #fee7e7; box-shadow: 0 0 0 2px #f7b4b4; }
        .event-card { display: grid; grid-template-columns: 4rem 2.9rem minmax(0, 1fr) 1.8rem; gap: .85rem; align-items: center; min-height: 7.4rem; padding: 1rem; margin: .65rem 0 0 .6rem; border: 1px solid #e8edf3; border-radius: .95rem; background: #fff; transition: .18s ease; }
        .event-card:hover { border-color: #cdd8e5; transform: translateY(-1px); box-shadow: 0 7px 20px rgba(15,35,63,.07); }
        .has-alert .event-card { border-color: #f3b8b8; background: linear-gradient(90deg, #fffafa, #fff); }
        .is-resolved .event-card { border-color: #b8e8cf; background: linear-gradient(90deg, #f6fffa, #fff); }
        .is-exit .event-card { margin-left: 2.5rem; }
        .event-time { text-align: center; }
        .event-time strong { display: block; font-size: 1.15rem; font-weight: 850; letter-spacing: -.02em; }
        .event-time span { display: block; margin-top: .15rem; color: #8290a3; font-size: .65rem; font-weight: 650; }
        .avatar { display: grid; place-items: center; flex: 0 0 auto; width: 2.85rem; height: 2.85rem; border-radius: .85rem; color: #205486; background: linear-gradient(145deg, #e4effb, #d4e7f8); font-size: .78rem; font-weight: 850; }
        .movement-label { margin-bottom: .12rem; font-size: .64rem; font-weight: 900; letter-spacing: .12em; }
        .is-entry .movement-label { color: #078653; }
        .is-exit .movement-label { color: #c65d0d; }
        .event-person h4, .inside-info h4 { margin: 0; color: var(--am-navy); font-size: .95rem; font-weight: 820; line-height: 1.2; }
        .person-meta { display: flex; flex-wrap: wrap; gap: .25rem .8rem; margin-top: .38rem; color: #66768b; font-size: .72rem; font-weight: 600; }
        .person-meta span { position: relative; }
        .person-meta span:not(:last-child)::after { content: "·"; position: absolute; right: -.52rem; color: #b1bac6; }
        .observation { margin: .5rem 0 0; color: #56677d; font-size: .72rem; font-style: italic; }
        .event-alert { display: inline-flex; align-items: center; gap: .35rem; margin-top: .55rem; padding: .35rem .55rem; border-radius: .5rem; color: #b91c1c; background: #feecec; font-size: .69rem; font-weight: 800; }
        .event-alert svg { width: .9rem; height: .9rem; }
        .event-resolved { display: inline-flex; align-items: center; gap: .35rem; margin-top: .55rem; padding: .35rem .55rem; border-radius: .5rem; color: #087b50; background: #e3f8ed; font-size: .69rem; font-weight: 800; }
        .event-resolved svg { width: .95rem; height: .95rem; }
        .force-exit-button { display: inline-flex; align-items: center; margin-top: .55rem; padding: .42rem .7rem; border-radius: .5rem; color: #fff; background: #c2410c; font-size: .69rem; font-weight: 800; box-shadow: 0 2px 5px rgba(154, 52, 18, .2); transition: .15s ease; }
        .force-exit-button:hover { background: #9a3412; }
        .force-exit-button.small { margin-top: .45rem; padding: .32rem .52rem; font-size: .63rem; }
        .event-link { display: grid; place-items: center; width: 1.8rem; height: 1.8rem; border-radius: .5rem; color: #8492a6; }
        .event-link:hover { color: #2563eb; background: #eff6ff; }
        .event-link svg { width: 1.1rem; }
        .inside-list { max-height: 31rem; overflow-y: auto; scrollbar-width: thin; }
        .inside-person { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border-bottom: 1px solid #edf1f5; }
        .inside-person:last-child { border-bottom: 0; }
        .inside-person .avatar { width: 2.5rem; height: 2.5rem; border-radius: 50%; font-size: .68rem; }
        .inside-info { min-width: 0; flex: 1; }
        .inside-info h4, .inside-info p { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .inside-info p { margin: .2rem 0; color: #66768b; font-size: .69rem; }
        .inside-info span { color: #8a97a8; font-size: .64rem; font-weight: 650; }
        .duration { flex: 0 0 auto; padding: .35rem .5rem; border-radius: .5rem; color: #256344; background: #e9f8f0; font-size: .67rem; font-weight: 800; }
        .duration.overdue { color: #a4470b; background: #fff0dc; }
        .alerts-list { padding: .45rem .85rem .85rem; }
        .alert-item { display: grid; grid-template-columns: 1.75rem minmax(0, 1fr) auto; gap: .6rem; align-items: start; padding: .75rem .35rem; border-bottom: 1px solid #edf1f5; }
        .alert-item:last-child { border-bottom: 0; }
        .alert-symbol { display: grid; place-items: center; width: 1.65rem; height: 1.65rem; border-radius: .5rem; color: #fff; background: #dc2626; font-weight: 900; }
        .alert-item.is-warning .alert-symbol { background: #dd7719; }
        .alert-item strong { display: block; color: #8f1d1d; font-size: .72rem; }
        .alert-item p { margin: .15rem 0 0; color: #66768b; font-size: .68rem; line-height: 1.3; }
        .alert-item > span { color: #8996a6; font-size: .65rem; font-weight: 700; }
        .all-clear { display: flex; align-items: center; gap: .75rem; padding: 1rem .35rem .65rem; }
        .all-clear svg { width: 2rem; height: 2rem; padding: .35rem; border-radius: 50%; color: #087b50; background: #e2f7ed; }
        .all-clear strong, .all-clear span { display: block; }
        .all-clear strong { font-size: .8rem; }
        .all-clear span { margin-top: .1rem; color: #718096; font-size: .68rem; }
        .empty-state { display: grid; justify-items: center; padding: 4rem 1rem; text-align: center; color: #7c8b9e; }
        .empty-state svg { width: 3rem; margin-bottom: .8rem; color: #a9b5c4; }
        .empty-state h4 { margin: 0; color: #40526a; font-weight: 800; }
        .empty-state p { margin: .3rem 0 0; font-size: .78rem; }
        .empty-state.compact { padding: 2rem 1rem; }
        .dark .access-monitor { --am-navy: #e7eef8; --am-muted: #9aa9bc; --am-line: #334155; --am-card: #172033; }
        .dark .stat-card, .dark .monitor-panel, .dark .monitor-filters, .dark .event-card { background: #172033; }
        .dark .search-box, .dark .filter-group { background: #101827; border-color: #39475b; }
        .dark .filter-group button.active { background: #263348; color: #dcecff; }
        .dark .search-box input { color: #e7eef8; }
        .dark .event-card { border-color: #344155; }
        .dark .has-alert .event-card { background: #291c24; border-color: #7f3941; }
        .dark .timeline-marker span { border-color: #172033; }
        .dark .timeline-event:not(:last-child)::before { background: #344155; }
        .dark .inside-person, .dark .alert-item, .dark .panel-header { border-color: #334155; }
        @media (max-width: 1180px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .monitor-filters { flex-wrap: wrap; }
            .search-box { flex-basis: 100%; }
            .monitor-layout { grid-template-columns: 1fr; }
            .monitor-sidebar { position: static; grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .monitor-hero { align-items: flex-start; padding: 1.25rem; }
            .pause-button { padding: .6rem; }
            .pause-button span { display: none; }
            .stats-grid { gap: .6rem; }
            .stat-card { min-height: 5rem; padding: .75rem; }
            .stat-card strong { font-size: 1.35rem; }
            .stat-icon { flex-basis: 2.25rem; height: 2.25rem; }
            .monitor-filters { align-items: stretch; }
            .filter-group { width: 100%; overflow-x: auto; }
            .filter-group button { flex: 1 0 auto; }
            .monitor-sidebar { grid-template-columns: 1fr; }
            .timeline { padding-inline: .55rem; }
            .timeline-event { grid-template-columns: 2rem minmax(0, 1fr); }
            .timeline-event:not(:last-child)::before { left: .97rem; }
            .timeline-marker span { width: 2rem; height: 2rem; border-width: .25rem; }
            .event-card { grid-template-columns: 3.3rem 2.5rem minmax(0, 1fr); gap: .55rem; margin-left: .35rem; padding: .8rem; }
            .is-exit .event-card { margin-left: 1rem; }
            .event-card .avatar { width: 2.45rem; height: 2.45rem; }
            .event-link { display: none; }
            .person-meta span { display: block; width: 100%; }
            .person-meta span::after { display: none; }
        }
    </style>
</x-filament-panels::page>
