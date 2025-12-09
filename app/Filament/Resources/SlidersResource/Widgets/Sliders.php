<?php

namespace App\Filament\Resources\SlidersResource\Widgets;

use App\Models\Slider;
use Filament\Widgets\Widget;

class Sliders extends Widget
{
    protected static string $view = 'filament.resources.sliders-resource.widgets.sliders';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getSliders()
    {
        return Slider::where('status', true)->get();
    }
}
