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

class Messages extends Page implements HasForms, HasTable
{

    use InteractsWithTable;
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.messages';

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
            return redirect()->route('auth.facebook');   
        }
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(Conversations::query())
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
                    // ->registerModalActions([
                    //     Action::make('send')
                    //         ->action( function (Conversations $record){
                    //             // Lógica para enviar el mensaje
                    //             $record->sendMessage($this->newMessage);

                    //             // Limpiar el campo de entrada
                    //             $this->newMessage = null;

                    //         }), 
                    // ])
                    // ->modalContentFooter(fn (Action $action): View => view(
                    //     'filament.pages.actions.sendmessage',
                    //     ['action' => $action],
                    // ))
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false)
                    ->slideOver()
            ])
            ;
    }


}
