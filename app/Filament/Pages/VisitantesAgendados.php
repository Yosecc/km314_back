<?php

namespace App\Filament\Pages;

use App\Models\FormControl;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class VisitantesAgendados extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.visitantes-agendados';

    // protected static string $slug = 'scheduled-visitors';

    protected static ?string $navigationLabel = 'Visitantes Agendados';
    protected static ?string $slug = 'scheduled-visitors';

    public function table(Table $table): Table
    {
        return $table
            ->query(FormControl::query()->orderBy('created_at','desc'))
            ->columns([
                TextColumn::make('id'),


            ])
            // ->actions([
            //     Action::make('Mensajes')
            //         ->modalHeading(fn (Conversations $record) => $record['from_name'] )
            //         ->modalContent(fn (Conversations $record): View => view(
            //             'filament.pages.actions.chat',
            //             ['record' => $record],
            //         ))
            //         ->stickyModalFooter()
            //         ->stickyModalHeader()
            //         ->modalSubmitAction(false)
            //         ->slideOver()
            // ])
            ;
    }

}
