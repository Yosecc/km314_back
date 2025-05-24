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
        return $table->columns(\App\Filament\Resources\InvoiceItemResource::getTableColumns('relation'));
    }
}
