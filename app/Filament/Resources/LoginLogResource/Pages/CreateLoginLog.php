<?php

namespace App\Filament\Resources\LoginLogResource\Pages;

use App\Filament\Resources\LoginLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoginLog extends CreateRecord
{
    protected static string $resource = LoginLogResource::class;
}
