<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Models\Activities;
use Filament\Tables\Table;
use App\Models\Conversations;
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
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Inbox extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.inbox';
    protected static ?string $navigationLabel = 'Mensajes';
    public string $activeTab = 'tablaMail';

    // Método para renderizar la tabla de correos
    public function tableMail(): Table
    {
        return Table::make($this)
            ->query(ConversationsMail::query()->orderBy('id', 'desc'))
            ->columns($this->camposTableMail())
            ->actions([
                Action::make('Mensajes')
                    ->modalHeading(fn (ConversationsMail $record) => $record['subject'] . " (".$record['from'].")" )
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
            ->query(Conversations::query()->orderBy('last_message_created_time','desc'))
            ->columns($this->camposTableFacebook())
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

    public function tableInstagram(): Table
    {
        return Table::make($this)
            ->query(Activities::query())
            ->columns($this->camposTableInstagram());
    }

    public function camposTableInstagram()
    {
        return [
            TextColumn::make('id')
        ];
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
            // TextColumn::make('id'),
            TextColumn::make('from_name'),
            TextColumn::make('last_message_created_time')->dateTime()
        ];
    }

    // Obtener la tabla correcta según la pestaña activa
    public function getTable(): Table
    {
        switch ($this->activeTab) {
            case 'tablaMail':
                return $this->tableMail();
                break;
            case 'tablaFacebook':
                return $this->tableFacebook();
            case 'tablaInstagram':
                return $this->tableInstagram();
            default:
                return $this->tableMail();
                break;
        }
    }
}
