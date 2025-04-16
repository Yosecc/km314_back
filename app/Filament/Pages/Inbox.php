<?php

namespace App\Filament\Pages;

use App\Http\Controllers\EmailService;

use App\Models\Activities;
use App\Models\Conversations;
use App\Models\ConversationsMail;
use App\Models\MessageProfileAssignments;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

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


        if( auth()->user()->hasRole('super_admin') ){
           $query = ConversationsMail::query()->orderBy('id', 'desc');
        }else{
            $mpa = MessageProfileAssignments::where('user_id', auth()->user()->id)
                ->where('type', 'mail')->get();

            $messagesIds = $mpa->pluck('message_id')->toArray();

            $query = ConversationsMail::query()
                ->whereIn('id', $messagesIds)
                ->orderBy('id', 'desc');

        }

        return Table::make($this)
            ->query($query)
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
                    ->slideOver(),
                Action::make('Asignar')
                    ->form([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->options(User::query()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, ConversationsMail $record): void {

                       MessageProfileAssignments::create([
                            'user_id' => $data['user_id'],
                            'message_id' => $record['id'],
                            'type' => 'mail',
                        ]);

                        $recipient = User::find( $data['user_id']);

                        Notification::make()
                            ->title('Nuevo Mensaje Asignado')
                            ->sendToDatabase($recipient);
                    }),
                Action::make('delete')
                    ->label('Mover a papelera')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(fn (ConversationsMail $record) => $record->moveTrash())
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
