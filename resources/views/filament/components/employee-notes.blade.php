<div class="space-y-4">
    @if($notes->isNotEmpty())
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @foreach($notes as $note)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $note->user->name ?? 'Usuario' }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $note->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if($note->status === 'active')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Activa
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $note->description }}</p>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $note->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <p class="mt-2">No hay notificaciones aún</p>
        </div>
    @endif
</div>
