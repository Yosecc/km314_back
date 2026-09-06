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
    class="space-y-5"
>
    <div class="rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-indigo-50 p-5 dark:border-sky-800 dark:from-sky-950/60 dark:to-indigo-950/60">
        <div class="mb-1 text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300">Formulario público · {{ $lot }}</div>
        <p class="text-sm text-gray-700 dark:text-gray-200">Compartí este enlace con la persona que completará el formulario.</p>

        <div class="mt-4 flex gap-2 rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <input
                x-ref="publicLink"
                type="text"
                readonly
                value="{{ $url }}"
                @click="$el.select()"
                class="min-w-0 flex-1 border-0 bg-transparent px-2 text-sm text-gray-700 outline-none ring-0 focus:ring-0 dark:text-gray-100"
                aria-label="Enlace público generado"
            >
            <button
                type="button"
                @click="copyLink()"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            >
                <x-heroicon-o-clipboard class="h-5 w-5" />
                <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
            </button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <a
            href="https://wa.me/?text={{ rawurlencode('Hola, completá tu formulario de ingreso desde este enlace: '.$url) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg"
        >
            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
            Compartir por WhatsApp
        </a>
        <a
            href="{{ $url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg"
        >
            <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5" />
            Abrir enlace
        </a>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-100">
        <div class="flex gap-3">
            <x-heroicon-o-light-bulb class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300" />
            <div>
                <p class="font-bold">Guardalo o compartilo antes de cerrar</p>
                <p class="mt-1 leading-relaxed">Por seguridad, este enlace no volverá a mostrarse en el sistema. Es de un solo uso, dura 7 días y se desactiva cuando la persona envía el formulario.</p>
            </div>
        </div>
    </div>
</div>
