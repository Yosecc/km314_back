<?php

namespace App\Filament\Resources\SlidersResource\Widgets;

use App\Models\Slider;
use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class Sliders extends Widget
{
    use HasWidgetShield;
    protected static string $view = 'filament.resources.sliders-resource.widgets.sliders';

    protected static ?string $heading = 'Carrousel';

    
    protected int | string | array $columnSpan = 'full';
    
    public function getSliders()
    {
        return Slider::where('status', true)->get();
    }
}
