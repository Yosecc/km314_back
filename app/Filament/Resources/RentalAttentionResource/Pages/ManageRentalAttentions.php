<?php

namespace App\Filament\Resources\RentalAttentionResource\Pages;

use App\Filament\Resources\RentalAttentionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRentalAttentions extends ManageRecords
{
    protected static string $resource = RentalAttentionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
