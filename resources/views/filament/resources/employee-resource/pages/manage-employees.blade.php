<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div
        class="employee-tutorial-page"
        x-data="{
            tutorialOpen: false,
            tutorialStep: 1,
            tutorialDirection: 1,
            storageKey: @js('km314.employee-tutorial.seen.user-' . auth()->id()),
            init() {
                if (localStorage.getItem(this.storageKey) !== '1') {
                    this.tutorialOpen = true;
                }
            },
            openTutorial() {
                this.tutorialStep = 1;
                this.tutorialDirection = 1;
                this.tutorialOpen = true;
            },
            markTutorialSeen() {
                localStorage.setItem(this.storageKey, '1');
            },
            closeTutorial() {
                this.markTutorialSeen();
                this.tutorialOpen = false;
                this.tutorialStep = 1;
                this.tutorialDirection = 1;
            }
        }"
        @open-employee-tutorial.window="openTutorial()"
    >
        <div class="flex flex-col gap-y-6">
            <x-filament-panels::resources.tabs />

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

            {{ $this->table }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
        </div>

        <div
            x-cloak
            x-show="tutorialOpen"
            x-transition.opacity
            class="et-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="et-tutorial-title"
            @click="closeTutorial()"
            @keydown.escape.window="if (tutorialOpen) closeTutorial()"
        >
            <section class="et-tutorial" @click.stop>
                <button type="button" class="et-close" aria-label="Cerrar tutorial" @click="closeTutorial()">
                    <x-heroicon-o-x-mark />
                </button>

                <div class="et-progress" aria-label="Progreso del tutorial">
                    <template x-for="number in 3" :key="number">
                        <button
                            type="button"
                            :class="{ active: tutorialStep === number, complete: tutorialStep > number }"
                            :aria-label="`Ir al paso ${number}`"
                            :aria-current="tutorialStep === number ? 'step' : null"
                            @click="tutorialDirection = number > tutorialStep ? 1 : -1; tutorialStep = number"
                        ><span x-text="number"></span></button>
                    </template>
                </div>

                <div class="et-stage" :style="`--tutorial-direction: ${tutorialDirection}`">
                    <div x-show="tutorialStep === 1" x-transition:enter="et-step-enter" x-transition:enter-start="et-step-enter-start" x-transition:enter-end="et-step-enter-end" x-transition:leave="et-step-leave" x-transition:leave-start="et-step-leave-start" x-transition:leave-end="et-step-leave-end" class="et-step">
                        <img src="{{ asset('images/employee-tutorial/preload-worker.webp') }}" alt="Una propietaria registra a un trabajador para tenerlo disponible en futuros formularios de control">
                        <div class="et-copy">
                            <span class="et-step-label">Paso 1 de 3 · Cargalo una sola vez</span>
                            <h2 id="et-tutorial-title">Tus trabajadores, listos para cada ingreso</h2>
                            <p>Registrar previamente a cada trabajador te permite encontrarlo y seleccionarlo rápidamente al crear un formulario de control. Así evitás volver a escribir sus datos cada vez que necesite ingresar al barrio.</p>
                            <ul>
                                <li>La ficha queda asociada al propietario y disponible para futuros formularios.</li>
                                <li>Estar registrado no habilita el ingreso por sí solo: primero debe completar la revisión.</li>
                            </ul>
                        </div>
                    </div>

                    <div x-show="tutorialStep === 2" x-transition:enter="et-step-enter" x-transition:enter-start="et-step-enter-start" x-transition:enter-end="et-step-enter-end" x-transition:leave="et-step-leave" x-transition:leave-start="et-step-leave-start" x-transition:leave-end="et-step-leave-end" class="et-step">
                        <img src="{{ asset('images/employee-tutorial/personal-and-vehicle-documents.webp') }}" alt="Una propietaria completa los datos personales, documentos y vehículo de un trabajador">
                        <div class="et-copy">
                            <span class="et-step-label">Paso 2 de 3 · Completá la ficha</span>
                            <h2>Información clara para un acceso seguro</h2>
                            <p>Cargá los datos personales del trabajador, su documentación vigente y, si ingresa con vehículo, también los datos y documentos correspondientes.</p>
                            <ul>
                                <li>Revisá que las imágenes sean legibles y que ningún documento esté vencido.</li>
                                <li>Una ficha completa agiliza la revisión y evita demoras al preparar el ingreso.</li>
                            </ul>
                        </div>
                    </div>

                    <div x-show="tutorialStep === 3" x-transition:enter="et-step-enter" x-transition:enter-start="et-step-enter-start" x-transition:enter-end="et-step-enter-end" x-transition:leave="et-step-leave" x-transition:leave-start="et-step-leave-start" x-transition:leave-end="et-step-leave-end" class="et-step">
                        <img src="{{ asset('images/employee-tutorial/review-and-renewal.webp') }}" alt="Administración revisa y aprueba la documentación de un trabajador antes de habilitar su acceso">
                        <div class="et-copy">
                            <span class="et-step-label">Paso 3 de 3 · Revisión y renovación</span>
                            <h2>La aprobación mantiene el acceso protegido</h2>
                            <p>Cada alta o actualización pasa por una revisión de Administración. Cuando la ficha queda aprobada, el trabajador puede seleccionarse en un formulario de control para solicitar su acceso.</p>
                            <div class="et-warning">
                                <x-heroicon-o-calendar-days />
                                <p><strong>Actualización cada {{ config('employees.documentation_renewal_months') }} meses.</strong> Si la documentación está vencida o pendiente de revisión, el trabajador no podrá agregarse a un formulario de control y, por lo tanto, no tendrá acceso al barrio.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="et-footer">
                    <button type="button" class="et-skip" @click="closeTutorial()" x-text="tutorialStep === 3 ? 'Cerrar' : 'Omitir tutorial'"></button>
                    <div>
                        <button type="button" class="et-prev" x-show="tutorialStep > 1" @click="tutorialDirection = -1; tutorialStep--"><x-heroicon-o-arrow-left /> Anterior</button>
                        <button type="button" class="et-next" x-show="tutorialStep < 3" @click="tutorialDirection = 1; tutorialStep++">Siguiente <x-heroicon-o-arrow-right /></button>
                        @if($this->getResource()::canCreate())
                            <a href="{{ $this->getResource()::getUrl('create') }}" class="et-start" x-show="tutorialStep === 3" @click="markTutorialSeen()">Cargar trabajador <x-heroicon-o-user-plus /></a>
                        @else
                            <button type="button" class="et-start" x-show="tutorialStep === 3" @click="closeTutorial()">Entendido <x-heroicon-o-check /></button>
                        @endif
                    </div>
                </footer>
            </section>
        </div>
    </div>

    <style>
        [x-cloak]{display:none!important}.et-backdrop{position:fixed;z-index:100000;inset:0;display:grid;place-items:center;background:#07131be8;padding:22px;backdrop-filter:blur(7px)}.et-tutorial{position:relative;width:min(940px,100%);max-height:calc(100vh - 44px);overflow:auto;border:1px solid #cbdfe8;border-radius:24px;background:#fff;color:#18303b;box-shadow:0 30px 90px #0008}.dark .et-tutorial{border-color:#334f5b;background:#102128;color:#edf8fb}.et-close{position:absolute;z-index:3;right:15px;top:15px;display:grid;place-items:center;width:36px;height:36px;border:1px solid #ffffff88;border-radius:11px;background:#123e4db8;color:#fff;backdrop-filter:blur(5px)}.et-close svg{width:20px}.et-progress{position:absolute;z-index:3;left:24px;top:22px;display:flex;gap:7px}.et-progress button{display:grid;place-items:center;width:27px;height:27px;border:1px solid #ffffff99;border-radius:99px;background:#17495aa8;color:#dff3f6;font-size:10px;font-weight:900;backdrop-filter:blur(5px);transition:width .3s ease,background-color .3s ease,color .3s ease}.et-progress button.active{width:40px;background:#fff;color:#0b7187}.et-progress button.complete{background:#17977e;color:#fff}.et-stage{display:grid;overflow:hidden}.et-step{grid-area:1/1;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(330px,.92fr);min-height:475px;will-change:transform,opacity}.et-step-enter,.et-step-leave{transition:opacity .36s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)}.et-step-enter-start{opacity:0;transform:translateX(calc(var(--tutorial-direction) * 34px)) scale(.985)}.et-step-enter-end,.et-step-leave-start{opacity:1;transform:translateX(0) scale(1)}.et-step-leave-end{opacity:0;transform:translateX(calc(var(--tutorial-direction) * -24px)) scale(.99)}.et-step>img{width:100%;height:100%;min-height:475px;object-fit:cover}.et-copy{display:flex;flex-direction:column;justify-content:center;padding:56px 40px 38px}.et-step-label{font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#0b8294}.et-copy h2{margin:9px 0 13px;font-size:26px;line-height:1.12;font-weight:900;color:inherit}.et-copy>p{font-size:13px;line-height:1.65;color:#526c77}.dark .et-copy>p{color:#b6ced5}.et-copy ul{display:grid;gap:8px;margin:17px 0 0;padding:0;list-style:none}.et-copy li{position:relative;padding-left:22px;font-size:11px;line-height:1.45;color:#526c77}.dark .et-copy li{color:#b6ced5}.et-copy li:before{content:'✓';position:absolute;left:0;top:-1px;display:grid;place-items:center;width:16px;height:16px;border-radius:99px;background:#e1f5ef;color:#148065;font-size:10px;font-weight:900}.dark .et-copy li:before{background:#21473f;color:#72e1bd}.et-warning{display:flex;align-items:flex-start;gap:10px;margin-top:18px;padding:13px;border:1px solid #f0d28c;border-radius:12px;background:#fff8e8;color:#81580c}.dark .et-warning{border-color:#745a27;background:#3e3018;color:#ffe0a0}.et-warning svg{width:22px;min-width:22px}.et-warning p{font-size:11px;line-height:1.5}.et-footer{position:sticky;z-index:2;bottom:0;display:flex;align-items:center;justify-content:space-between;gap:14px;border-top:1px solid #dbe8ec;background:#fffffff2;padding:15px 22px;backdrop-filter:blur(7px)}.dark .et-footer{border-color:#2f4a55;background:#102128f2}.et-footer>div{display:flex;align-items:center;gap:8px}.et-footer button,.et-footer a{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:9px 13px;font-size:11px;font-weight:850}.et-footer svg{width:16px}.et-skip{color:#687f88}.dark .et-skip{color:#abc4cb}.et-prev{border:1px solid #cadfe5;color:#42636d}.dark .et-prev{border-color:#385965;color:#c9e1e7}.et-next,.et-start{background:#0d7588;color:#fff;box-shadow:0 5px 15px #0d758830}.et-next:hover,.et-start:hover{background:#095e70}@media(prefers-reduced-motion:reduce){.et-step-enter,.et-step-leave,.et-progress button{transition-duration:.01ms!important}}@media(max-width:720px){.et-backdrop{padding:10px}.et-tutorial{max-height:calc(100vh - 20px);border-radius:18px}.et-step{display:block;min-height:0}.et-step>img{height:220px;min-height:0}.et-copy{padding:25px 21px 22px}.et-copy h2{font-size:22px}.et-progress{left:15px;top:14px}.et-close{right:12px;top:12px}.et-footer{align-items:stretch;flex-direction:column;padding:12px 16px}.et-footer>div{justify-content:flex-end;flex-wrap:wrap}}
    </style>
</x-filament-panels::page>
