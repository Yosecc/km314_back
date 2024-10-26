<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Models\Activities;
use Filament\Tables\Table;
use App\Models\ConversationsMail;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\EmailService;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Inbox extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.inbox';

    public string $activeTab = 'tablaMail';

    // Método para renderizar la tabla de correos
    public function tableMail(): Table
    {
        return Table::make($this)
            ->query(ConversationsMail::query()->orderBy('id', 'desc'))
            ->columns($this->camposTableMail())
            ->actions([
                Action::make('Mensajes')
                    ->modalHeading(fn (ConversationsMail $record) => $record['subject'] )
                    ->modalContent(fn (ConversationsMail $record): View => view(
                        'filament.pages.actions.messagesMail',
                        ['record' => $record],
                    ))
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false)
                    ->slideOver()
            ]);
    }

    // Método para renderizar la tabla de Facebook
    public function tableFacebook(): Table
    {
        return Table::make($this)
            ->query(Activities::query()->orderBy('id', 'desc'))
            ->columns($this->camposTableFacebook());
    }

    // Columnas para tablaMail
    public function camposTableMail()
    {
        return [
            IconColumn::make('leido')
                ->label('')
                ->boolean()
                ->falseIcon('heroicon-o-information-circle')
                ->falseColor('warning'),
            TextColumn::make('from')
                ->icon('heroicon-m-envelope')
                ->copyable()
                ->copyMessage('Email address copied')
                ->copyMessageDuration(1500)    
                ->description(fn (ConversationsMail $record): string => $record->subject),
            TextColumn::make('date')->dateTime()
        ];
    }

    // Columnas para tablaFacebook
    public function camposTableFacebook()
    {
        return [
            TextColumn::make('id'),
        ];
    }

    // Obtener la tabla correcta según la pestaña activa
    public function getTable(): Table
    {
        return $this->activeTab === 'tablaMail' ? $this->tableMail() : $this->tableFacebook();
    }
}
