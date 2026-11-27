<x-filament-panels::page>
    @php($data = $this->monitorData)
    @php($options = $this->filterOptions)
    <div class="im" wire:poll.60s>
        <header class="im-hero">
            <div class="im-hero-copy">
                <span>Seguimiento comercial en tiempo real</span>
                <h2>Monitor de Interesados</h2>
                <p>Priorizá contactos, registrá conversaciones y mantené cada oportunidad en movimiento. Actualizado {{ $data['updatedAt'] }}</p>
            </div>
            <div class="im-hero-art" aria-hidden="true"><i></i><i></i><i></i></div>
            <nav class="im-hero-actions">
                <a href="{{ $data['calendarUrl'] }}" class="secondary"><x-heroicon-o-calendar-days /> Calendario</a>
                @if($data['canCreate'])<a href="{{ $data['createUrl'] }}" class="primary"><x-heroicon-o-user-plus /> Nuevo interesado</a>@endif
            </nav>
        </header>

        <section class="im-stats" aria-label="Resumen">
            <button wire:click="$set('timingFilter', 'active')" class="new"><strong>{{ $data['stats']['new'] }}</strong><span>Nuevos</span><small>Sin iniciar gestión</small></button>
            <button wire:click="$set('timingFilter', 'today')" class="today"><strong>{{ $data['stats']['today'] }}</strong><span>Para hoy</span><small>Contactos programados</small></button>
            <button wire:click="$set('timingFilter', 'overdue')" class="overdue"><strong>{{ $data['stats']['overdue'] }}</strong><span>Vencidos</span><small>Necesitan atención</small></button>
            <button wire:click="$set('timingFilter', 'high')" class="high"><strong>{{ $data['stats']['high'] }}</strong><span>Nivel alto</span><small>Oportunidades prioritarias</small></button>
            <button wire:click="$set('timingFilter', 'without_follow_up')" class="missing"><strong>{{ $data['stats']['without_follow_up'] }}</strong><span>Sin seguimiento</span><small>Falta próxima acción</small></button>
        </section>

        <section class="im-filters">
            <div class="im-search"><x-heroicon-o-magnifying-glass /><input wire:model.live.debounce.400ms="search" placeholder="Buscar nombre, DNI, teléfono, email, ciudad, observaciones…"></div>
            <select wire:model.live="timingFilter"><option value="active">Activos</option><option value="today">Para hoy</option><option value="overdue">Vencidos</option><option value="without_follow_up">Sin seguimiento</option><option value="high">Nivel alto</option><option value="closed">Finalizados</option></select>
            <select wire:model.live="statusFilter"><option value="">Todos los estados</option>@foreach($options['statuses'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
            <select wire:model.live="levelFilter"><option value="">Todos los niveles</option>@foreach($options['levels'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
            <select wire:model.live="originFilter"><option value="">Todos los orígenes</option>@foreach($options['origins'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
            <select wire:model.live="typeFilter"><option value="">Todos los tipos</option>@foreach($options['types'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
            <select wire:model.live="assignedFilter"><option value="">Todos los responsables</option>@foreach($options['users'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
            <select wire:model.live="sort"><option value="priority">Prioridad operativa</option><option value="next_contact">Próximo contacto</option><option value="last_contact">Último contacto</option><option value="created">Más recientes</option><option value="name">Nombre</option></select>
            <button wire:click="clearFilters" class="im-clear"><x-heroicon-o-x-mark /> Limpiar</button>
        </section>

        <section class="im-grid" wire:loading.class="is-loading">
            @forelse($data['records'] as $record)
                @php($followUp = $record->nextFollowUp)
                @php($lastContact = $record->latestContact)
                @php($isOverdue = $record->next_contact_at?->isPast())
                <article @class(['im-card', 'is-overdue'=>$isOverdue, 'is-today'=>$record->next_contact_at?->isToday()])>
                    <div class="im-card-top">
                        <div class="im-avatar">{{ mb_strtoupper(mb_substr($record->first_name,0,1).mb_substr($record->last_name,0,1)) }}</div>
                        <div class="im-person"><h3>{{ $record->full_name }}</h3><p>{{ $record->interestedOrigins?->name ?? 'Sin origen' }} <b>·</b> {{ $record->assignedUser?->name ?? 'Sin responsable' }}</p></div>
                        <div class="im-badges">
                            @if($record->interestedLevel)<span class="level {{ mb_strtolower($record->interestedLevel->name) }}">{{ $record->interestedLevel->name }}</span>@endif
                            @if($record->interestedStatus)<span class="status {{ $record->interestedStatus->color }}">{{ $record->interestedStatus->name }}</span>@endif
                        </div>
                    </div>

                    <div class="im-contact-links">
                        @if($record->phone)<a href="tel:{{ $record->phone }}"><x-heroicon-o-phone />{{ $record->phone }}</a>@endif
                        @if($record->whatsapp_url)<a href="{{ $record->whatsapp_url }}" target="_blank" rel="noopener" class="wa"><x-heroicon-o-chat-bubble-oval-left-ellipsis />WhatsApp</a>@endif
                        @if($record->email)<a href="mailto:{{ $record->email }}"><x-heroicon-o-envelope />{{ $record->email }}</a>@endif
                    </div>

                    <div class="im-timeline">
                        <div><span>Último contacto</span>
                            @if($lastContact)
                                <strong>{{ $lastContact->contacted_at->format('d/m/Y H:i') }} · {{ \App\Models\InterestedContact::CHANNELS[$lastContact->channel] ?? $lastContact->channel }}</strong>
                                <small>{{ $lastContact->result ?: ($lastContact->notes ? \Illuminate\Support\Str::limit($lastContact->notes, 62) : 'Sin detalle') }}</small>
                            @else <strong class="muted">Todavía no contactado</strong><small>Registrá la primera conversación</small> @endif
                        </div>
                        <div @class(['next', 'danger'=>$isOverdue, 'warning'=>$record->next_contact_at?->isToday()])><span>Próxima acción</span>
                            @if($followUp)
                                <strong>{{ $followUp->due_at->isToday() ? 'Hoy '.$followUp->due_at->format('H:i') : $followUp->due_at->format('d/m/Y H:i') }}</strong>
                                <small>{{ $followUp->title }}{{ $followUp->assignedUser ? ' · '.$followUp->assignedUser->name : '' }}</small>
                            @else <strong class="muted">Sin seguimiento</strong><small>Programá el próximo paso</small> @endif
                        </div>
                    </div>

                    @if($record->interestedType || $record->lote || $record->propertie)
                        <div class="im-meta">
                            @if($record->interestedType)<span><b>Tipo:</b> {{ $record->interestedType->name }}</span>@endif
                            @if($record->lote)<span><b>Lote:</b> {{ $record->lote->getNombre() }}</span>@endif
                            @if($record->propertie)<span><b>Propiedad:</b> {{ $record->propertie->identificador }}</span>@endif
                        </div>
                    @endif

                    <footer class="im-actions">
                        <div>{{ ($this->registerContactAction)(['interested' => $record->id]) }} {{ ($this->scheduleFollowUpAction)(['interested' => $record->id]) }}</div>
                        @if($followUp)<div>{{ ($this->completeFollowUpAction)(['followUp' => $followUp->id]) }} {{ ($this->rescheduleFollowUpAction)(['followUp' => $followUp->id]) }}</div>@endif
                        <a href="{{ \App\Filament\Resources\InterestedResource::getUrl('edit',['record'=>$record]) }}">Ver ficha <span>→</span></a>
                    </footer>
                </article>
            @empty
                <div class="im-empty"><x-heroicon-o-user-group /><h3>No hay interesados para mostrar</h3><p>Probá otra combinación de filtros o cargá un nuevo interesado.</p></div>
            @endforelse
        </section>

        <div class="im-pagination">{{ $data['records']->links() }}</div>
    </div>

    <style>
        .im{--bg:#f7f5fc;--card:#fff;--line:#e4def1;--ink:#241b36;--muted:#756b85;--violet:#6d43a5;--violet2:#8a62bd;color:var(--ink)}.dark .im{--bg:#15111e;--card:#211a2d;--line:#493b5e;--ink:#f7f1ff;--muted:#b8aac9;--violet:#b58be9;--violet2:#9367cb}.im-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:1fr auto;gap:20px;min-height:185px;border-radius:24px;background:linear-gradient(125deg,#382150,#70459a 58%,#9b68bf);padding:30px 34px;color:#fff;box-shadow:0 18px 45px #4825682b}.im-hero-copy{position:relative;z-index:2;max-width:680px}.im-hero-copy>span{font-size:10px;font-weight:900;letter-spacing:.13em;text-transform:uppercase;color:#e4cef8}.im-hero h2{margin:7px 0 8px;font-size:29px;font-weight:950}.im-hero p{max-width:620px;font-size:12px;line-height:1.6;color:#eadff3}.im-hero-actions{position:relative;z-index:2;display:flex;align-items:flex-end;gap:9px}.im-hero-actions a{display:flex;align-items:center;gap:7px;border-radius:11px;padding:10px 13px;font-size:11px;font-weight:850}.im-hero-actions svg{width:17px}.im-hero-actions .primary{background:#fff;color:#56327c}.im-hero-actions .secondary{border:1px solid #ffffff66;background:#ffffff16;color:#fff}.im-hero-art{position:absolute;right:23%;top:-60px;width:310px;height:310px;border:1px solid #ffffff1f;border-radius:50%}.im-hero-art i{position:absolute;border-radius:50%;background:#ffffff13}.im-hero-art i:nth-child(1){inset:50px}.im-hero-art i:nth-child(2){inset:100px}.im-hero-art i:nth-child(3){left:134px;top:134px;width:42px;height:42px;background:#ffffff31}.im-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:16px 0}.im-stats button{position:relative;text-align:left;border:1px solid var(--line);border-radius:16px;background:var(--card);padding:16px 17px;transition:.16s}.im-stats button:hover{transform:translateY(-2px);box-shadow:0 8px 20px #39234c12}.im-stats strong{display:block;font-size:26px;line-height:1;font-weight:950;color:var(--violet)}.im-stats span{display:block;margin-top:7px;font-size:11px;font-weight:850}.im-stats small{font-size:9px;color:var(--muted)}.im-stats .overdue strong{color:#d24e55}.im-stats .today strong{color:#d58a19}.im-stats .high strong{color:#318b66}.im-stats .missing strong{color:#697386}.im-filters{display:grid;grid-template-columns:minmax(280px,2fr) repeat(4,minmax(135px,1fr));gap:9px;margin-bottom:16px;border:1px solid var(--line);border-radius:17px;background:var(--card);padding:13px}.im-filters select,.im-search{height:39px;border:1px solid var(--line);border-radius:10px;background:var(--bg);font-size:10px;color:var(--ink)}.im-filters select{padding:0 10px}.im-search{display:flex;align-items:center;gap:8px;padding:0 11px}.im-search svg{width:17px;color:var(--muted)}.im-search input{width:100%;border:0;background:transparent;font-size:11px;outline:0;color:var(--ink)}.im-clear{display:flex;align-items:center;justify-content:center;gap:5px;border-radius:10px;color:var(--muted);font-size:10px;font-weight:750}.im-clear svg{width:15px}.im-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;transition:opacity .2s}.im-grid.is-loading{opacity:.45}.im-card{overflow:hidden;border:1px solid var(--line);border-radius:19px;background:var(--card);box-shadow:0 7px 20px #38284a0b}.im-card.is-overdue{border-left:4px solid #df555b}.im-card.is-today:not(.is-overdue){border-left:4px solid #e4a132}.im-card-top{display:grid;grid-template-columns:auto 1fr auto;gap:11px;align-items:center;padding:16px 17px 11px}.im-avatar{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:linear-gradient(145deg,#eee5f8,#d7c5eb);color:#633c8e;font-size:13px;font-weight:950}.dark .im-avatar{background:#443257;color:#e2c9ff}.im-person h3{font-size:15px;font-weight:900}.im-person p{margin-top:2px;font-size:9px;color:var(--muted)}.im-person p b{padding:0 3px}.im-badges{display:flex;max-width:180px;flex-wrap:wrap;justify-content:flex-end;gap:5px}.im-badges span{border-radius:99px;padding:4px 7px;font-size:8px;font-weight:900;text-transform:uppercase}.im-badges .level{background:#f0edf4;color:#665d71}.im-badges .level.alto{background:#dff5e9;color:#267454}.im-badges .level.medio{background:#fff1d6;color:#9a6817}.im-badges .status{background:#eee8f6;color:#65418c}.im-badges .status.success{background:#dff5e9;color:#267454}.im-badges .status.danger{background:#ffe1e2;color:#a5363b}.im-badges .status.warning{background:#fff1d6;color:#8d6018}.im-contact-links{display:flex;flex-wrap:wrap;gap:7px;border-block:1px solid var(--line);background:var(--bg);padding:9px 17px}.im-contact-links a{display:flex;align-items:center;gap:5px;border-radius:7px;background:var(--card);padding:5px 7px;font-size:9px;font-weight:750;color:var(--violet)}.im-contact-links svg{width:13px}.im-contact-links .wa{color:#16885a}.im-timeline{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:13px 17px}.im-timeline>div{min-height:74px;border:1px solid var(--line);border-radius:12px;padding:10px}.im-timeline span{display:block;font-size:8px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;color:var(--muted)}.im-timeline strong{display:block;margin-top:5px;font-size:10px;font-weight:850}.im-timeline small{display:block;margin-top:4px;font-size:9px;line-height:1.35;color:var(--muted)}.im-timeline .muted{color:var(--muted)}.im-timeline .danger{border-color:#eeb3b5;background:#fff7f7}.dark .im-timeline .danger{border-color:#714347;background:#2d1c23}.im-timeline .danger strong{color:#c83e47}.im-timeline .warning{border-color:#edd19d;background:#fffaf1}.dark .im-timeline .warning{border-color:#715c36;background:#2c261a}.im-meta{display:flex;flex-wrap:wrap;gap:7px;padding:0 17px 12px}.im-meta span{border-radius:7px;background:var(--bg);padding:5px 7px;font-size:8px;color:var(--muted)}.im-actions{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:7px;border-top:1px solid var(--line);padding:10px 14px}.im-actions>div{display:flex;gap:5px}.im-actions>a{margin-left:auto;font-size:9px;font-weight:850;color:var(--violet)}.im-actions>a span{font-size:13px}.im-empty{grid-column:1/-1;display:grid;place-items:center;min-height:280px;border:1px dashed var(--line);border-radius:20px;background:var(--card);text-align:center;padding:30px}.im-empty svg{width:42px;color:var(--violet)}.im-empty h3{margin-top:10px;font-size:15px;font-weight:900}.im-empty p{font-size:10px;color:var(--muted)}.im-pagination{margin-top:18px}
        @media(max-width:1100px){.im-stats{grid-template-columns:repeat(3,1fr)}.im-filters{grid-template-columns:repeat(3,1fr)}.im-search{grid-column:1/-1}}@media(max-width:820px){.im-hero{grid-template-columns:1fr;padding:24px}.im-hero-actions{align-items:flex-start}.im-grid{grid-template-columns:1fr}.im-stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.im-hero h2{font-size:23px}.im-hero-actions{flex-direction:column;align-items:stretch}.im-hero-actions a{justify-content:center}.im-stats{grid-template-columns:1fr 1fr}.im-filters{grid-template-columns:1fr}.im-search{grid-column:auto}.im-card-top{grid-template-columns:auto 1fr}.im-badges{grid-column:1/-1;justify-content:flex-start}.im-timeline{grid-template-columns:1fr}.im-actions{align-items:stretch;flex-direction:column}.im-actions>div{flex-wrap:wrap}.im-actions>a{margin:4px 0 0}}
    </style>
</x-filament-panels::page>
