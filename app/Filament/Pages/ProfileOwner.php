<?php

namespace App\Filament\Pages;

use App\Models\OwnerStatus;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;

class ProfileOwner extends Page implements HasForms
{
    use HasPageShield;
    // use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // protected static string $view = 'filament.pages.profile-owner';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('owner') && auth()->user()->owner_id;
    }

    public function mount()
    {
        $ownerId = Auth::user()->owner_id;
        if ($ownerId) {
            return redirect()->route('filament.admin.resources.owners.profile-owner', ['record' => auth()->user()->owner_id]);
        }
    }


}
