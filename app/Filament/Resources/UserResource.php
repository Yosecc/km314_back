<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Owner;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $label = 'usuario';
    protected static ?string $navigationGroup = 'Configuración';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('owner_id')
                ->label(__("general.Owner"))
                ->relationship(name: 'owner', modifyQueryUsing: fn (Builder $query) => $query->orderBy('first_name')->orderBy('last_name'))
                ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                ->searchable(['first_name', 'last_name'])
                ->afterStateUpdated(function(Set $set, $state){
                    $owner = Owner::find($state);
					if(!$owner){
					return ;
					}
                    $set('name', $owner->first_name . ' ' . $owner->last_name);
                    $set('roles',[3]);
                    $set('email', $owner->email);
                })
                ->live(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(
                        table: 'users', // Nombre de la tabla
                        column: 'email', // Nombre de la columna
                        ignoreRecord: true // Ignora el registro actual al validar
                    )
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')->default(now()->format('Y-m-d H:m:s')),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))

                    ->required(function($context){
                        return $context == 'edit' ? false : true;
                    })
                    ->maxLength(255),
                    Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = bcrypt($data['password']);

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')

                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
