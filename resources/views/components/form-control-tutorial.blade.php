@props([
    'createUrl' => null,
    'canCreate' => false,
])

<div
    class="form-control-tutorial"
    x-data="{
        tutorialOpen: false,
        tutorialStep: 1,
        tutorialDirection: 1,
        storageKey: @js('km314.form-control-tutorial.seen.user-' . auth()->id()),
        init() {
            if (localStorage.getItem(this.storageKey) !== '1') this.tutorialOpen = true
        },
        openTutorial() {
            this.tutorialStep = 1
            this.tutorialDirection = 1
            this.tutorialOpen = true
        },
        markTutorialSeen() {
            localStorage.setItem(this.storageKey, '1')
        },
        closeTutorial() {
            this.markTutorialSeen()
            this.tutorialOpen = false
            this.tutorialStep = 1
            this.tutorialDirection = 1
        },
    }"
    @open-form-control-tutorial.window="openTutorial()"
>
    <div
        x-cloak
        x-show="tutorialOpen"
        x-transition.opacity
        class="fct-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="fct-title"
        @click="closeTutorial()"
        @keydown.escape.window="if (tutorialOpen) closeTutorial()"
    >
        <section class="fct-modal" @click.stop>
            <button type="button" class="fct-close" aria-label="Cerrar tutorial" @click="closeTutorial()"><x-heroicon-o-x-mark /></button>

            <div class="fct-progress" aria-label="Progreso del tutorial">
                <template x-for="number in 4" :key="number">
                    <button
                        type="button"
                        :class="{ active: tutorialStep === number, complete: tutorialStep > number }"
                        :aria-current="tutorialStep === number ? 'step' : null"
                        @click="tutorialDirection = number > tutorialStep ? 1 : -1; tutorialStep = number"
                    ><span x-text="number"></span></button>
                </template>
            </div>

            <div class="fct-stage" :style="`--tutorial-direction: ${tutorialDirection}`">
                <div x-show="tutorialStep === 1" x-transition:enter="fct-enter" x-transition:enter-start="fct-enter-start" x-transition:enter-end="fct-enter-end" x-transition:leave="fct-leave" x-transition:leave-start="fct-leave-start" x-transition:leave-end="fct-leave-end" class="fct-step">
                    <img src="{{ asset('images/form-control-tutorial/purpose-and-types.webp') }}" alt="Una propietaria elige el tipo de persona para crear un acceso controlado al barrio">
                    <div class="fct-copy">
                        <span class="fct-label">Paso 1 de 4 · Acceso organizado</span>
                        <h2 id="fct-title">Un formulario para cada ingreso al barrio</h2>
                        <p>El formulario de control permite informar quién necesita ingresar, a qué lote se dirige y durante qué período estará autorizado.</p>
                        <ul>
                            <li>Inquilinos y visitantes de inquilinos.</li>
                            <li>Trabajadores y proveedores.</li>
                            <li>Visitas temporales, de más de 24 horas y recurrentes.</li>
                        </ul>
                    </div>
                </div>

                <div x-show="tutorialStep === 2" x-transition:enter="fct-enter" x-transition:enter-start="fct-enter-start" x-transition:enter-end="fct-enter-end" x-transition:leave="fct-leave" x-transition:leave-start="fct-leave-start" x-transition:leave-end="fct-leave-end" class="fct-step">
                    <img src="{{ asset('images/form-control-tutorial/information-and-documents.webp') }}" alt="Una propietaria completa datos personales, documentación y vehículos en el formulario">
                    <div class="fct-copy">
                        <span class="fct-label">Paso 2 de 4 · Información completa</span>
                        <h2>Cargá los datos necesarios para validar el acceso</h2>
                        <p>Completá la información personal de cada persona y adjuntá la documentación solicitada según el tipo de ingreso.</p>
                        <ul>
                            <li>Datos personales y responsables del grupo.</li>
                            <li>Documentos y fechas de vencimiento cuando corresponda.</li>
                            <li>Vehículos, mascotas y otra información necesaria.</li>
                        </ul>
                    </div>
                </div>

                <div x-show="tutorialStep === 3" x-transition:enter="fct-enter" x-transition:enter-start="fct-enter-start" x-transition:enter-end="fct-enter-end" x-transition:leave="fct-leave" x-transition:leave-start="fct-leave-start" x-transition:leave-end="fct-leave-end" class="fct-step">
                    <img src="{{ asset('images/form-control-tutorial/administration-review.webp') }}" alt="Administración revisa la información y aprueba el formulario de control">
                    <div class="fct-copy">
                        <span class="fct-label">Paso 3 de 4 · Revisión</span>
                        <h2>Administración verifica el formulario</h2>
                        <p>Una vez enviado, el formulario pasa por un proceso de revisión. Podés seguir su estado y atender cualquier observación desde el monitor.</p>
                        <ul>
                            <li>Pendiente mientras administración revisa la solicitud.</li>
                            <li>Autorizado cuando la información es aprobada.</li>
                            <li>Rechazado, vencido o expirado cuando requiere atención.</li>
                        </ul>
                        <div class="fct-warning"><x-heroicon-o-shield-check /><p>Solo un formulario autorizado habilita el ingreso al barrio.</p></div>
                    </div>
                </div>

                <div x-show="tutorialStep === 4" x-transition:enter="fct-enter" x-transition:enter-start="fct-enter-start" x-transition:enter-end="fct-enter-end" x-transition:leave="fct-leave" x-transition:leave-start="fct-leave-start" x-transition:leave-end="fct-leave-end" class="fct-step">
                    <img src="{{ asset('images/form-control-tutorial/qr-dni-and-validity.webp') }}" alt="Seguridad valida un formulario autorizado mediante el código QR dentro de las fechas permitidas">
                    <div class="fct-copy">
                        <span class="fct-label">Paso 4 de 4 · Ingreso y salida</span>
                        <h2>Acceso ágil con QR o DNI</h2>
                        <p>Cuando el formulario está autorizado, las personas pueden entrar y salir presentando el QR del formulario o su número de DNI en el puesto de acceso.</p>
                        <ul>
                            <li>El personal de acceso verifica la autorización vigente.</li>
                            <li>El monitor permite consultar estado, lote, personas y fechas.</li>
                            <li>El permiso funciona solamente dentro del rango seleccionado.</li>
                        </ul>
                        <div class="fct-warning"><x-heroicon-o-calendar-days /><p>Fuera de las fechas y horarios autorizados, el formulario no habilita el acceso.</p></div>
                    </div>
                </div>
            </div>

            <footer class="fct-footer">
                <button type="button" class="fct-skip" @click="closeTutorial()" x-text="tutorialStep === 4 ? 'Cerrar' : 'Omitir tutorial'"></button>
                <div>
                    <button type="button" class="fct-prev" x-show="tutorialStep > 1" @click="tutorialDirection = -1; tutorialStep--"><x-heroicon-o-arrow-left /> Anterior</button>
                    <button type="button" class="fct-next" x-show="tutorialStep < 4" @click="tutorialDirection = 1; tutorialStep++">Siguiente <x-heroicon-o-arrow-right /></button>
                    @if($canCreate && $createUrl)
                        <a href="{{ $createUrl }}" class="fct-start" x-show="tutorialStep === 4" @click="markTutorialSeen()">Crear formulario <x-heroicon-o-plus /></a>
                    @else
                        <button type="button" class="fct-start" x-show="tutorialStep === 4" @click="closeTutorial()">Entendido <x-heroicon-o-check /></button>
                    @endif
                </div>
            </footer>
        </section>
    </div>
</div>

<style>
    [x-cloak]{display:none!important}.fct-backdrop{position:fixed;z-index:100000;inset:0;display:grid;place-items:center;background:#120b20e8;padding:22px;backdrop-filter:blur(7px)}.fct-modal{position:relative;width:min(960px,100%);max-height:calc(100vh - 44px);overflow:auto;border:1px solid #ded4ec;border-radius:24px;background:#fff;color:#241936;box-shadow:0 30px 90px #0008}.dark .fct-modal{border-color:#4b3e61;background:#1b1427;color:#f5efff}.fct-close{position:absolute;z-index:3;right:15px;top:15px;display:grid;place-items:center;width:36px;height:36px;border:1px solid #ffffff88;border-radius:11px;background:#352050bd;color:#fff;backdrop-filter:blur(5px)}.fct-close svg{width:20px}.fct-progress{position:absolute;z-index:3;left:24px;top:22px;display:flex;gap:7px}.fct-progress button{display:grid;place-items:center;width:27px;height:27px;border:1px solid #ffffff99;border-radius:99px;background:#4b2d70a8;color:#eee4fa;font-size:10px;font-weight:900;backdrop-filter:blur(5px);transition:width .3s ease,background-color .3s ease,color .3s ease}.fct-progress button.active{width:40px;background:#fff;color:#6a43a0}.fct-progress button.complete{background:#7b53ad;color:#fff}.fct-stage{display:grid;overflow:hidden}.fct-step{grid-area:1/1;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(340px,.92fr);min-height:500px;will-change:transform,opacity}.fct-enter,.fct-leave{transition:opacity .36s cubic-bezier(.22,1,.36,1),transform .36s cubic-bezier(.22,1,.36,1)}.fct-enter-start{opacity:0;transform:translateX(calc(var(--tutorial-direction) * 34px)) scale(.985)}.fct-enter-end,.fct-leave-start{opacity:1;transform:translateX(0) scale(1)}.fct-leave-end{opacity:0;transform:translateX(calc(var(--tutorial-direction) * -24px)) scale(.99)}.fct-step>img{width:100%;height:100%;min-height:500px;object-fit:cover}.fct-copy{display:flex;flex-direction:column;justify-content:center;padding:58px 40px 38px}.fct-label{font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#7950a8}.fct-copy h2{margin:9px 0 13px;font-size:26px;line-height:1.12;font-weight:900;color:inherit}.fct-copy>p{font-size:13px;line-height:1.65;color:#675b73}.dark .fct-copy>p{color:#c8bad5}.fct-copy ul{display:grid;gap:8px;margin:17px 0 0;padding:0;list-style:none}.fct-copy li{position:relative;padding-left:22px;font-size:11px;line-height:1.45;color:#675b73}.dark .fct-copy li{color:#c8bad5}.fct-copy li:before{content:'✓';position:absolute;left:0;top:-1px;display:grid;place-items:center;width:16px;height:16px;border-radius:99px;background:#efe7f8;color:#694197;font-size:10px;font-weight:900}.dark .fct-copy li:before{background:#3d2d51;color:#d3aff7}.fct-warning{display:flex;align-items:flex-start;gap:10px;margin-top:18px;padding:13px;border:1px solid #e5ca8c;border-radius:12px;background:#fff8e7;color:#77520d}.dark .fct-warning{border-color:#705825;background:#3b2e17;color:#ffe0a0}.fct-warning svg{width:22px;min-width:22px}.fct-warning p{font-size:11px;line-height:1.5}.fct-footer{position:sticky;z-index:2;bottom:0;display:flex;align-items:center;justify-content:space-between;gap:14px;border-top:1px solid #e4dcec;background:#fffffff2;padding:15px 22px;backdrop-filter:blur(7px)}.dark .fct-footer{border-color:#443653;background:#1b1427f2}.fct-footer>div{display:flex;align-items:center;gap:8px}.fct-footer button,.fct-footer a{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:9px 13px;font-size:11px;font-weight:850}.fct-footer svg{width:16px}.fct-skip{color:#796f82}.dark .fct-skip{color:#baaeca}.fct-prev{border:1px solid #d9cde5;color:#604e70}.dark .fct-prev{border-color:#544264;color:#ded0eb}.fct-next,.fct-start{background:#70469e;color:#fff;box-shadow:0 5px 15px #5f388c30}.fct-next:hover,.fct-start:hover{background:#59357f}@media(prefers-reduced-motion:reduce){.fct-enter,.fct-leave,.fct-progress button{transition-duration:.01ms!important}}@media(max-width:720px){.fct-backdrop{padding:10px}.fct-modal{max-height:calc(100vh - 20px);border-radius:18px}.fct-step{display:block;min-height:0}.fct-step>img{height:220px;min-height:0}.fct-copy{padding:25px 21px 22px}.fct-copy h2{font-size:22px}.fct-progress{left:15px;top:14px}.fct-close{right:12px;top:12px}.fct-footer{align-items:stretch;flex-direction:column;padding:12px 16px}.fct-footer>div{justify-content:flex-end;flex-wrap:wrap}}
</style>
