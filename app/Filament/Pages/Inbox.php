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
           $query = ConversationsMail::query()->orderBy('date', 'desc');
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
                        [
                            'record' => $record,
                            'isAssigned' => MessageProfileAssignments::where('message_id', $record->message_id)->where('type', 'mail')->exists(),
                        ],
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
                            'message_id' => $record['message_id'],
                            'type' => 'mail',
                        ]);

                        $record->moveAssigned();

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
                    ->action(fn (ConversationsMail $record) => $record->messageMoveTrash())
            ]);
    }

    // Método para renderizar la tabla de Facebook
    public function tableFacebook(): Table
    {

        if( auth()->user()->hasRole('super_admin') ){
            $query = Conversations::query()->orderBy('last_message_created_time','desc');
        }else{
            $mpa = MessageProfileAssignments::where('user_id', auth()->user()->id)
                ->where('type', 'facebook')->get();

            $messagesIds = $mpa->pluck('message_id')->toArray();
            $query = Conversations::query()
                ->whereIn('id', $messagesIds)
                // ->orderBy('id', 'desc')
                ;

        }
        return Table::make($this)
            ->query($query)
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
                    ->slideOver(),
                Action::make('Asignar')
                    ->form([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->options(User::query()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, Conversations $record): void {

                       MessageProfileAssignments::create([
                            'user_id' => $data['user_id'],
                            'message_id' => $record['id'],
                            'type' => 'facebook',
                        ]);

                        $recipient = User::find( $data['user_id']);

                        Notification::make()
                            ->title('Nuevo Mensaje Asignado')
                            ->sendToDatabase($recipient);
                    }),
            ]);
    }

    public function tableInstagram(): Table
    {

        return Table::make($this)
            ->query(Activities::query())
            ->columns(components: $this->camposTableInstagram());
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
                ->label(label: '')
                ->boolean()
                ->falseIcon('heroicon-m-envelope')
                ->falseColor('warning')
                ->trueIcon('heroicon-m-envelope-open')
                ->trueColor('grey'),
            TextColumn::make('from')
                ->searchable()
                ->formatStateUsing(function (string $state, $record){

                    $string = $state;
                    $mpa = MessageProfileAssignments::where('message_id', $record->message_id)
                            ->where('type', 'mail')
                            ->first();

                            if($mpa){
                                $user = User::find($mpa->user_id);
                                $string .= ' Asignado a: ' .$user->name;
                            }
                    return $string;
                })
                ->color(function($record){
                    $mpa = MessageProfileAssignments::where('message_id', $record->message_id)
                            ->where('type', 'mail')->first();

                    return $mpa ? 'info' : 'grey';
                })
                ->description(fn (ConversationsMail $record): string => $record->subject),
            TextColumn::make('date')->dateTime()
        ];
    }

    // Columnas para tablaFacebook
    public function camposTableFacebook()
    {
        return [
            // TextColumn::make('id'),
            TextColumn::make('from_name')
            ->label('Nombre')
            ->description(function (string $state, $record){

                $string = '';
                $mpa = MessageProfileAssignments::where('message_id', $record->id)
                        ->where('type', 'facebook')
                        ->first();

                        if($mpa){
                            $user = User::find($mpa->user_id);
                            $string .= ' Asignado a: ' .$user->name;
                        }

                return $string;
            })
            ->color(function($record){
                $mpa = MessageProfileAssignments::where('message_id', $record->id)
                        ->where('type', 'facebook')->first();

                return $mpa ? 'info' : 'grey';
            }),
            TextColumn::make('last_message_created_time')->label('Fecha')->dateTime()
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
