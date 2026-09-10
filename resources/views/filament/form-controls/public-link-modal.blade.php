<div
    x-data="{
        url: @js($url),
        copied: false,
        async copyLink() {
            this.$refs.publicLink.select()
            try { await navigator.clipboard.writeText(this.url) }
            catch (error) { document.execCommand('copy') }
            this.copied = true
            setTimeout(() => this.copied = false, 2500)
        }
    }"
    class="plm"
>
    <div class="plm-intro">
        <div class="plm-intro-icon"><x-heroicon-o-link /></div>
        <div>
            <span>Formulario público · {{ $lot }}</span>
            <h3>Compartí este enlace con tu invitado</h3>
            <p>La persona podrá completar el formulario para este lote.</p>
        </div>
    </div>

    <div class="plm-link-box">
        <div class="plm-link-label"><x-heroicon-o-paper-airplane /> Enlace de un solo uso</div>
        <div class="plm-copy-row">
            <input x-ref="publicLink" type="text" readonly value="{{ $url }}" @click="$el.select()" aria-label="Enlace público generado">
            <button type="button" @click="copyLink()">
                <x-heroicon-o-clipboard />
                <span x-text="copied ? '¡Copiado!' : 'Copiar'" aria-live="polite"></span>
            </button>
        </div>
    </div>

    <div class="plm-actions">
        <a href="https://wa.me/?text={{ rawurlencode('Hola, completá tu formulario de ingreso desde este enlace: '.$url) }}" target="_blank" rel="noopener noreferrer" class="plm-whatsapp">
            <x-heroicon-o-chat-bubble-left-right />
            <span>Compartir por WhatsApp</span>
        </a>
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="plm-open">
            <x-heroicon-o-arrow-top-right-on-square />
            <span>Abrir enlace</span>
        </a>
    </div>

    <div class="plm-notice">
        <div class="plm-notice-icon"><x-heroicon-o-shield-exclamation /></div>
        <div>
            <strong>Guardalo antes de cerrar</strong>
            <p>Por seguridad, este enlace no podrá volver a consultarse desde el sistema.</p>
            <div class="plm-rules">
                <span><x-heroicon-o-user /> Un solo uso</span>
                <span><x-heroicon-o-clock /> Dura 7 días</span>
                <span><x-heroicon-o-lock-closed /> Se desactiva al enviarlo</span>
            </div>
        </div>
    </div>

    <style>
        .plm{--plm-card:#f8fbff;--plm-line:#d7e2ef;--plm-ink:#182535;--plm-muted:#637488;--plm-primary:#2563b8;--plm-primary-soft:#eaf3ff;--plm-input:#fff;--plm-notice:#fff8e9;--plm-notice-line:#efd49b;--plm-notice-ink:#754b08;display:grid;gap:16px;color:var(--plm-ink)}
        .dark .plm{--plm-card:#152131;--plm-line:#344a61;--plm-ink:#edf5ff;--plm-muted:#a9bbcf;--plm-primary:#72b3ff;--plm-primary-soft:#172e49;--plm-input:#0d1723;--plm-notice:#382b15;--plm-notice-line:#72572d;--plm-notice-ink:#ffe0a0}
        .plm-intro{display:flex;align-items:center;gap:14px;padding:2px 2px 0}.plm-intro-icon{display:grid;place-items:center;width:45px;height:45px;flex:none;border-radius:14px;background:linear-gradient(145deg,#377bd2,#245aa6);color:#fff;box-shadow:0 8px 17px #245aa633}.plm-intro-icon svg{width:23px}.plm-intro span{display:block;font-size:10px;font-weight:850;letter-spacing:.09em;text-transform:uppercase;color:var(--plm-primary)}.plm-intro h3{margin:3px 0 2px;font-size:16px;line-height:1.2;font-weight:850;color:var(--plm-ink)}.plm-intro p{margin:0;font-size:12px;color:var(--plm-muted)}
        .plm-link-box{border:1px solid var(--plm-line);border-radius:15px;background:var(--plm-card);padding:12px}.plm-link-label{display:flex;align-items:center;gap:6px;margin:0 2px 8px;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--plm-muted)}.plm-link-label svg{width:14px;color:var(--plm-primary)}.plm-copy-row{display:flex;align-items:center;gap:8px;border:1px solid var(--plm-line);border-radius:10px;background:var(--plm-input);padding:5px}.plm-copy-row input{min-width:0;flex:1;border:0!important;background:transparent!important;padding:8px 9px!important;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;color:var(--plm-ink)!important;outline:0!important;box-shadow:none!important}.plm-copy-row button{display:inline-flex;align-items:center;gap:7px;flex:none;border-radius:8px;background:var(--plm-primary);padding:9px 13px;color:#fff;font-size:12px;font-weight:850;transition:filter .16s,transform .16s}.plm-copy-row button:hover{filter:brightness(1.08);transform:translateY(-1px)}.plm-copy-row button svg{width:16px}
        .plm-actions{display:grid;grid-template-columns:1.35fr .85fr;gap:10px}.plm-actions a{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:45px;border-radius:11px;padding:10px 14px;font-size:12px;font-weight:850;transition:transform .16s,filter .16s}.plm-actions a:hover{transform:translateY(-1px)}.plm-actions svg{width:18px}.plm-whatsapp{background:#1c9a61;color:#fff;box-shadow:0 7px 15px #1683532b}.plm-whatsapp:hover{filter:brightness(1.06);color:#fff}.plm-open{border:1px solid var(--plm-line);background:var(--plm-card);color:var(--plm-ink)}.plm-open:hover{border-color:var(--plm-primary);color:var(--plm-primary)}
        .plm-notice{display:flex;gap:11px;border:1px solid var(--plm-notice-line);border-radius:13px;background:var(--plm-notice);padding:13px;color:var(--plm-notice-ink)}.plm-notice-icon{display:grid;place-items:center;width:31px;height:31px;flex:none;border-radius:9px;background:#e8b95f2b}.plm-notice-icon svg{width:18px}.plm-notice strong{display:block;font-size:12px;font-weight:850}.plm-notice p{margin:3px 0 0;font-size:11px;line-height:1.45;opacity:.9}.plm-rules{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}.plm-rules span{display:inline-flex;align-items:center;gap:4px;border:1px solid #bd852f42;border-radius:999px;background:#ffffff5c;padding:4px 7px;font-size:9px;font-weight:800}.dark .plm-rules span{background:#0a100b2e}.plm-rules svg{width:11px}
        @media(max-width:520px){.plm-actions{grid-template-columns:1fr}.plm-intro{align-items:flex-start}.plm-copy-row{align-items:stretch;flex-direction:column}.plm-copy-row button{justify-content:center}.plm-copy-row input{width:100%}}
    </style>
</div>
