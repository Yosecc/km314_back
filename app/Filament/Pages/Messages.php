<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Activities;
use Filament\Tables\Table;
use App\Models\Conversations;

use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Messages extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.messages';

    protected static bool $shouldRegisterNavigation = false;

    public $newMessage;

    public function __construct()
    {
        if(!Cache::store('file')->has('access_token')){
            return redirect()->route('auth.facebook');
        }
    }

    public function mount()
    {
        if(!Cache::store('file')->has('access_token')){
            // dd('llega');
            $redirectUri = config('app.url') . '/auth/facebook';
            header('Location: '.$redirectUri);
            die();
            return redirect()->route('auth.facebook');
        }
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(Conversations::query()->orderBy('last_message_created_time','desc'))
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('from_name'),
                TextColumn::make('last_message_created_time')->dateTime()
            ])
            ->actions([
                Action::make('Mensajes')
                    ->modalHeading(fn (Conversations $record) => $record['from_name'] )
                    ->modalContent(fn (Conversations $record): View => view(
                        'filament.pages.actions.chat',
                        ['record' => $record],
                    ))
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false)
                    ->slideOver()
            ]);
    }


}
