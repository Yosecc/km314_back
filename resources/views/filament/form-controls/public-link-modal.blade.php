<div
    x-data="{
        url: @js($url),
        copied: false,
        async copyLink() {
            this.$refs.publicLink.select()
            try {
                await navigator.clipboard.writeText(this.url)
            } catch (error) {
                document.execCommand('copy')
            }
            this.copied = true
            setTimeout(() => this.copied = false, 2500)
        }
    }"
    class="space-y-4"
>
    <div class="overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 p-5 dark:border-sky-800 dark:from-sky-950/60 dark:via-gray-900 dark:to-indigo-950/60">
        <div class="flex items-start gap-3">
            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-500/25"><x-heroicon-o-link class="h-6 w-6" /></div>
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300">Enlace único · {{ $lot }}</div>
                <p class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-200">Compartilo con la persona que completará el formulario de ingreso.</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-sky-200/90 bg-white/90 p-2 shadow-sm dark:border-sky-800 dark:bg-gray-950/70">
            <label class="mb-1 block px-2 text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tu enlace para compartir</label>
            <div class="flex items-center gap-2">
                <input x-ref="publicLink" type="text" readonly value="{{ $url }}" @click="$el.select()" class="min-w-0 flex-1 border-0 bg-transparent px-2 font-mono text-xs text-gray-700 outline-none ring-0 focus:ring-0 dark:text-gray-100" aria-label="Enlace público generado">
                <button type="button" @click="copyLink()" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <x-heroicon-o-clipboard class="h-5 w-5" />
                    <span x-text="copied ? '¡Copiado!' : 'Copiar'" aria-live="polite"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-[1.35fr_.65fr]">
        <a href="https://wa.me/?text={{ rawurlencode('Hola, completá tu formulario de ingreso desde este enlace: '.$url) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg">
            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" /> Enviar por WhatsApp
        </a>
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
            <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5" /> Abrir
        </a>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-950 dark:border-amber-700/70 dark:bg-amber-950/35 dark:text-amber-100">
        <div class="flex gap-3">
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300"><x-heroicon-o-light-bulb class="h-5 w-5" /></div>
            <div>
                <p class="font-bold">Guardalo o compartilo antes de cerrar</p>
                <p class="mt-1 leading-relaxed text-amber-900/85 dark:text-amber-100/85">Por seguridad, este enlace no vuelve a mostrarse dentro del sistema.</p>
                <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-bold">
                    <span class="rounded-full bg-white px-2.5 py-1 text-amber-800 shadow-sm dark:bg-amber-950/70 dark:text-amber-200">Un solo uso</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-amber-800 shadow-sm dark:bg-amber-950/70 dark:text-amber-200">Vence en 7 días</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-amber-800 shadow-sm dark:bg-amber-950/70 dark:text-amber-200">Se desactiva al enviarlo</span>
                </div>
            </div>
        </div>
    </div>
</div>
