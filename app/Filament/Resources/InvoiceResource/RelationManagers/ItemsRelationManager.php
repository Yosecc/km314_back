<?php
namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\InvoiceItemResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Ítems de Factura';

    public function form(Form $form): Form
    {
        // Reutiliza el schema DRY desde InvoiceItemResource, omitiendo 'invoice_id'
        return $form->schema(\App\Filament\Resources\InvoiceItemResource::getFormSchema('relation'));
    }

    public function table(Table $table): Table
    {
        // Reutiliza las columnas DRY desde InvoiceItemResource, omitiendo la columna de factura
        $columns = \App\Filament\Resources\InvoiceItemResource::getTableColumns('relation');
        // Agregar el resumen solo a la columna 'amount'
        foreach ($columns as &$column) {
            if (method_exists($column, 'getName') && $column->getName() === 'amount') {
                $column = $column->summarize([
                    Sum::make()->money('ARS')->label('Total'),
                ]);
            }
        }
        return $table
                ->columns($columns)
                ->headerActions([
                    Tables\Actions\CreateAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->label('Imprimir PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn ($livewire) => route('factura.pdf', $livewire->getOwnerRecord()->id))
                        ->openUrlInNewTab(),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
    }

    // Emitir evento al crear, editar o eliminar un ítem
    protected function afterCreate(): void
    {
        $this->emitUp('refreshInvoiceTotal');
    }
    protected function afterEdit(): void
    {
        $this->emitUp('refreshInvoiceTotal');
    }
    protected function afterDelete(): void
    {
        $this->emitUp('refreshInvoiceTotal');
    }
}
