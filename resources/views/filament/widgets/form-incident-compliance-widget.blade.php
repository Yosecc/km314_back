<div class="filament-widget">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                📋 Estado de Formularios Obligatorios
            </h3>
        </div>

        @if($status)
            <div class="mt-4 space-y-4">
                {{-- Estado general --}}
                @if($status['is_fully_compliant'])
                    <div class="rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                    ¡Estás al día! ✅
                                </h3>
                                <div class="mt-1 text-sm text-green-700 dark:text-green-300">
                                    Todos tus formularios obligatorios están completos.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Formularios vencidos --}}
                @if(count($status['overdue']) > 0)
                    <div class="rounded-lg bg-red-50 p-4 border border-red-200 dark:bg-red-900/20 dark:border-red-800">
                        <div class="flex items-center mb-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    ⚠️ Formularios Vencidos ({{ count($status['overdue']) }})
                                </h3>
                            </div>
                        </div>
                        <div class="space-y-2">
                            @foreach($status['overdue'] as $item)
                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-3 border">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $item['requirement']->formIncidentType->name }}
                                        </div>
                                        <div class="text-sm text-red-600 dark:text-red-400">
                                            Vencido desde: {{ $item['deadline']->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <a href="{{ route('filament.admin.resources.form-incident-responses.create', ['form_incident_type_id' => $item['requirement']->form_incident_type_id]) }}"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Completar Ahora
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Formularios pendientes --}}
                @if(count($status['pending']) > 0)
                    <div class="rounded-lg bg-yellow-50 p-4 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800">
                        <div class="flex items-center mb-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    ⏰ Formularios Pendientes ({{ count($status['pending']) }})
                                </h3>
                            </div>
                        </div>
                        <div class="space-y-2">
                            @foreach($status['pending'] as $item)
                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-3 border">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $item['requirement']->formIncidentType->name }}
                                        </div>
                                        <div class="text-sm text-yellow-600 dark:text-yellow-400">
                                            Vence: {{ $item['deadline']->format('d/m/Y H:i') }}
                                            @if($item['hours_until_deadline'] > 0)
                                                (en {{ $item['hours_until_deadline'] }} horas)
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('filament.admin.resources.form-incident-responses.create', ['form_incident_type_id' => $item['requirement']->form_incident_type_id]) }}"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        Completar
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Formularios completados --}}
                @if(count($status['completed']) > 0)
                    <div class="rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                        <div class="flex items-center mb-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                    ✅ Formularios Completados ({{ count($status['completed']) }})
                                </h3>
                            </div>
                        </div>
                        <div class="space-y-2">
                            @foreach($status['completed'] as $item)
                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-3 border">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $item['requirement']->formIncidentType->name }}
                                        </div>
                                        <div class="text-sm text-green-600 dark:text-green-400">
                                            Completado: {{ $item['response']->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <a href="{{ route('filament.admin.resources.form-incident-responses.view', $item['response']->id) }}"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        Ver Respuesta
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="mt-4 text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Sin formularios asignados</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No tienes formularios obligatorios configurados.</p>
            </div>
        @endif
    </div>
</div>
