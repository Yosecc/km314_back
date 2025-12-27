<x-filament-widgets::widget>
    <x-filament::section>
        <div x-data="{
            currentSlide: 0,
            slides: {{ $this->getSliders()->count() }},
            autoplay: null,
            init() {
                this.startAutoplay();
            },
            startAutoplay() {
                this.autoplay = setInterval(() => {
                    this.next();
                }, 5000);
            },
            stopAutoplay() {
                clearInterval(this.autoplay);
            },
            next() {
                this.currentSlide = (this.currentSlide + 1) % this.slides;
            },
            prev() {
                this.currentSlide = (this.currentSlide - 1 + this.slides) % this.slides;
            },
            goToSlide(index) {
                this.currentSlide = index;
                this.stopAutoplay();
                this.startAutoplay();
            }
        }" 
        @mouseenter="stopAutoplay()" 
        @mouseleave="startAutoplay()"
        class="relative w-full overflow-hidden rounded-lg" 
        style="height: 400px;">
            
            <!-- Slides -->
            @foreach($this->getSliders() as $index => $slider)
                <div x-show="currentSlide === {{ $index }}"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 transform translate-x-full"
                     x-transition:enter-end="opacity-100 transform translate-x-0"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 transform translate-x-0"
                     x-transition:leave-end="opacity-0 transform -translate-x-full"
                     class="absolute inset-0 w-full h-full">
                    <img src="{{ asset('storage/' . $slider->img) }}" 
                         alt="Slide {{ $index + 1 }}"
                         class="w-full h-full object-cover">
                </div>
            @endforeach

            <!-- Controles de navegación -->
            <button @click="prev()" 
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 rounded-full p-2 shadow-lg transition-all">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <button @click="next()" 
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 rounded-full p-2 shadow-lg transition-all">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>

            <!-- Indicadores -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                @foreach($this->getSliders() as $index => $slider)
                    <button @click="goToSlide({{ $index }})"
                            :class="currentSlide === {{ $index }} ? 'bg-white w-8' : 'bg-white/50 w-3'"
                            class="h-3 rounded-full transition-all duration-300">
                    </button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
