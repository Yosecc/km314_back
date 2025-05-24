<?php
namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\InvoiceItemResource;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Ítems de Factura';

    public static function form(Form $form): Form
    {
        // Reutiliza el formulario de InvoiceItemResource
        return InvoiceItemResource::form($form);
    }

    public static function table(Table $table): Table
    {
        // Reutiliza la tabla de InvoiceItemResource
        return InvoiceItemResource::table($table);
    }
}
