<?php

namespace App\Http\Controllers\Api;

use App\Models\Slider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class Main extends Controller
{
    public function sliders()
    {
        return Slider::where('status',1)->get()->map(function($slide){
            
            $slide['img'] =  config('app.url').Storage::url($slide['img']);

            return $slide;

        })->pluck('img');

    }
}
