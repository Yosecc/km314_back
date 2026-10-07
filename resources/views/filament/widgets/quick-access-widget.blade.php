<x-filament-widgets::widget>
    <section class="qaw">
        <header class="qaw-head">
            <div class="qaw-title">
                <span></span>
                <div><h3>Accesos Rápidos</h3><p>Sus enlaces favoritos, siempre a mano</p></div>
            </div>
            {{ $this->settingsAction }}
        </header>

        @if($links->isEmpty())
            <div class="qaw-empty">
                <div><x-heroicon-o-cursor-arrow-rays /></div>
                <h4>Todavía no hay accesos configurados</h4>
                <p>Use el botón Configurar para agregar los enlaces que visita con frecuencia.</p>
            </div>
        @else
            <div class="qaw-grid">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" @if($link['external']) target="_blank" rel="noopener noreferrer" @endif class="qaw-card">
                        <div class="qaw-icon"><x-dynamic-component :component="$link['icon']" /></div>
                        <div><strong>{{ $link['name'] }}</strong><span>{{ $link['external'] ? parse_url($link['url'], PHP_URL_HOST) : $link['url'] }}</span></div>
                        <x-heroicon-o-arrow-up-right class="qaw-arrow" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <x-filament-actions::modals />

    <style>
        .qaw{--qaw-bg:#f7f9fc;--qaw-card:#fff;--qaw-line:#dce3ed;--qaw-ink:#1d2939;--qaw-muted:#718096;--qaw-main:#4868c7;overflow:hidden;border:1px solid var(--qaw-line);border-radius:18px;background:var(--qaw-bg);padding:17px;color:var(--qaw-ink);box-shadow:0 8px 24px #23385c0d}.dark .qaw{--qaw-bg:#121722;--qaw-card:#1b2230;--qaw-line:#374154;--qaw-ink:#f2f6ff;--qaw-muted:#aab5c7;--qaw-main:#91aaff;box-shadow:0 12px 34px #0004}.qaw-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px;padding:0 3px}.qaw-title{display:flex;align-items:center;gap:10px}.qaw-title>span{width:9px;height:29px;border-radius:99px;background:linear-gradient(#7d96e7,#3f5db9);box-shadow:0 0 0 4px #5472ce1c}.qaw-title h3{margin:0;font-size:14px;font-weight:850;color:var(--qaw-ink)}.qaw-title p{margin:1px 0 0;font-size:10px;color:var(--qaw-muted)}.qaw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.qaw-card{position:relative;display:flex;min-height:88px;align-items:center;gap:12px;border:1px solid var(--qaw-line);border-radius:15px;background:var(--qaw-card);padding:15px;color:inherit;transition:.18s}.qaw-card:hover{transform:translateY(-2px);border-color:#8fa5e2;box-shadow:0 9px 22px #314d9918}.dark .qaw-card:hover{border-color:#667ab1;box-shadow:0 9px 24px #0005}.qaw-icon{display:grid;place-items:center;width:42px;height:42px;flex:none;border-radius:12px;background:#536fc916;color:var(--qaw-main)}.dark .qaw-icon{background:#91aaff1c}.qaw-icon svg{width:22px}.qaw-card>div:nth-child(2){min-width:0}.qaw-card strong,.qaw-card span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.qaw-card strong{font-size:12px;font-weight:850;color:var(--qaw-ink)}.qaw-card span{max-width:180px;margin-top:4px;font-size:9px;color:var(--qaw-muted)}.qaw-arrow{position:absolute;right:10px;top:10px;width:14px;color:var(--qaw-muted);opacity:.55}.qaw-empty{display:flex;min-height:135px;flex-direction:column;align-items:center;justify-content:center;border:1px dashed var(--qaw-line);border-radius:15px;background:var(--qaw-card);padding:20px;text-align:center}.qaw-empty>div{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#536fc916;color:var(--qaw-main)}.qaw-empty svg{width:22px}.qaw-empty h4{margin:8px 0 2px;font-size:12px;font-weight:850;color:var(--qaw-ink)}.qaw-empty p{margin:0;font-size:10px;color:var(--qaw-muted)}
        @media(max-width:1000px){.qaw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:520px){.qaw{padding:13px}.qaw-head{align-items:flex-start}.qaw-grid{grid-template-columns:1fr}}
    </style>
</x-filament-widgets::widget>
