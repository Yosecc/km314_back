<?php

namespace App\Filament\Resources\ActivitiesResource\Pages;


use App\Filament\Resources\ActivitiesResource;
use Filament\Resources\Pages\EditRecord;

class ActivitiesPageEdit extends EditRecord
{
    protected static string $resource = ActivitiesResource::class;

    protected function beforeFill(): void
    {
       dd('este es');
    }

}
