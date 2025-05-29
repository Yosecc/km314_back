<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use App\Filament\Resources\AccountStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewAccountStatus extends ViewRecord
{
    protected static string $resource = AccountStatusResource::class;

    public static function infolist(Infolist $infolist): Infolist
    {



        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('owner_id'),
                Infolists\Components\TextEntry::make('balance'),
                Infolists\Components\TextEntry::make('total_invoiced')
                    ->columnSpanFull(),
            ]);
    }
}
