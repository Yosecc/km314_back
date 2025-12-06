<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>

    @if (!$isDisabled())
        <div  class="grid grid-rows-4 grid-flow-col gap-4" x-data="{state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} }">
            @foreach($opciones as $key => $value)
                <div
                    x-data="{ id: {{ $key }} }"
                    @click="state = {{ $key }}; setTimeout(() => { const quickCodeInput = document.querySelector('input[name=quick_code]'); if(quickCodeInput) quickCodeInput.focus(); }, 200);"
                    class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 cursor-pointer"
                    :style="state == id && { backgroundColor: 'rgb(245 158 11)' }"
                >
                <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">

                    <span>{{ $value }} </span>

                </div>

                </div>
            @endforeach
        </div>
        @else
        <div  class="grid grid-rows-4 grid-flow-col gap-4" x-data="{state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} }">
            @foreach($opciones as $key => $value)
                <div
                    x-data="{ id: {{ $key }} }"
                    class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 cursor-pointer"
                    :style="state == id && { backgroundColor: 'rgb(245 158 11)' }"
                >
                <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">

                    <span>{{ $value }} </span>

                </div>

                </div>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
