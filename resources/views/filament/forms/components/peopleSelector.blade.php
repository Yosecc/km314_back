<div x-data="{
    state: $wire.$entangle('{{ $getStatePath() }}'),
    selectedPeople: [],
    init() {
        // Inicializar con los valores actuales
        if (Array.isArray(this.state)) {
            this.selectedPeople = this.state;
        }
    }
}" class="space-y-4">
    @if(count($personas) > 0)
        <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
            @foreach($personas as $persona)
                <div 
                    @click="
                        if (selectedPeople.includes({{ $persona['id'] }})) {
                            selectedPeople = selectedPeople.filter(id => id !== {{ $persona['id'] }});
                        } else {
                            selectedPeople.push({{ $persona['id'] }});
                        }
                        state = selectedPeople;
                    "
                    :class="{
                        'ring-2 ring-primary-600 bg-primary-50 dark:bg-primary-900/20': selectedPeople.includes({{ $persona['id'] }}),
                        'ring-1 ring-gray-300 dark:ring-gray-700 hover:ring-gray-400 dark:hover:ring-gray-600': !selectedPeople.includes({{ $persona['id'] }})
                    }"
                    class="relative p-4 rounded-lg cursor-pointer transition-all duration-200"
                >
                    <!-- Checkbox visual -->
                    <div class="absolute top-3 right-3">
                        <div 
                            :class="{
                                'bg-primary-600 border-primary-600': selectedPeople.includes({{ $persona['id'] }}),
                                'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700': !selectedPeople.includes({{ $persona['id'] }})
                            }"
                            class="w-5 h-5 rounded border-2 flex items-center justify-center"
                        >
                            <svg 
                                x-show="selectedPeople.includes({{ $persona['id'] }})"
                                class="w-3 h-3 text-white" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Contenido de la card -->
                    <div class="pr-8" style="padding-left: 30px">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-lg mb-1">
                            {{ $persona['nombre'] }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ $persona['descripcion'] }}
                        </p>

                        <!-- Badges de estado -->
                        @if(isset($persona['badges']) && count($persona['badges']) > 0)
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach($persona['badges'] as $badge)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['color'] }}">
                                        @if(isset($badge['icon']))
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                        {{ $badge['texto'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Alertas de vencimiento -->
                        @if(isset($persona['vencimientos']) && count($persona['vencimientos']) > 0)
                            <div class="mt-3 space-y-2">
                                @foreach($persona['vencimientos'] as $vencimiento)
                                    <div class="text-xs" style="background: #E74C3C; padding: 10px; border-radius: 7px; color: white;">
                                        {{ $vencimiento['texto'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="font-medium">No hay personas disponibles</p>
            <p class="text-sm mt-1">Realiza una búsqueda por DNI o patente</p>
        </div>
    @endif
</div>
