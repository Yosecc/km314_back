<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Filament\Resources\OwnerResource\RelationManagers;
use App\Models\Owner;
use App\Models\OwnerStatus;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Propietario';
    protected static ?string $label = 'propietario';

    protected static ?string $recordTitleAttribute = 'first_name';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('dni')
                        ->label(__("general.DNI"))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->numeric(),

                    Forms\Components\TextInput::make('cuit')
                        ->label(__("general.CUIT"))
                        ->numeric(),

                    Forms\Components\TextInput::make('first_name')
                        // ->live()
                        // ->afterStateUpdated(function($state, Set $set){
                        //     $set('user.name', $state);
                        // })
                        ->label(__("general.FirstName"))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('last_name')
                        ->label(__("general.LastName"))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label(__("general.Email"))
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255)
                        // ->live()
                        // ->afterStateUpdated(function($state, Set $set){
                        //     $set('user.email', $state);
                        // })
                        ,

                    Forms\Components\TextInput::make('phone')
                        ->label(__("general.Phone")),

                    Forms\Components\TextInput::make('address')
                        ->label(__("general.Address"))
                        ->maxLength(255),

                    Forms\Components\TextInput::make('number')
                        ->label(__("general.number"))
                        ->numeric(),

                    Forms\Components\TextInput::make('piso')
                        ->label(__("general.piso"))
                        ->numeric(),

                    Forms\Components\TextInput::make('dto')
                        ->label(__("general.dto")),

                    Forms\Components\TextInput::make('city')
                        ->label(__("general.City"))
                        ->maxLength(255),

                    Forms\Components\TextInput::make('state')
                        ->label(__("general.State"))
                        ->maxLength(255),

                    Forms\Components\TextInput::make('zip_code')
                        ->label(__("general.ZipCode"))
                        ->maxLength(255),

                    Forms\Components\TextInput::make('country')
                        ->label(__("general.Country"))
                        ->maxLength(255),

                    // Forms\Components\DatePicker::make('birthdate')
                    //     ->label(__("general.Birthdate")),

                    // Forms\Components\TextInput::make('gender')
                    //     ->label(__("general.Gender"))
                    //     ->maxLength(255),

                    // Forms\Components\TextInput::make('profile_picture')
                    //     ->label(__("general.ProfilePicture"))
                    //     ->maxLength(255),

                    Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),



                    Forms\Components\Select::make('owner_status_id')
                        ->label('Estatus')
                        ->options(OwnerStatus::get()->pluck('name','id')->toArray())
                        // ->relationship(name: 'status', titleAttribute: 'name')
                        ->nullable()

                    // Fieldset::make('Usuario')
                    //     ->relationship('user')
                    //     ->schema([
                    //         Forms\Components\TextInput::make('name')
                    //             ->required()
                    //             ->maxLength(255),
                    //         Forms\Components\TextInput::make('email')
                    //             ->email()
                    //             ->unique(ignoreRecord: true)
                    //             ->required()
                    //             ->maxLength(255),
                    //         // Forms\Components\DateTimePicker::make('email_verified_at')->default(now()->format('Y-m-d H:m:s')),
                    //         Forms\Components\TextInput::make('password')
                    //             ->password()
                    //             ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    //             ->dehydrated(fn ($state) => filled($state))
                    //             ->required(function($context){
                    //                 return $context == 'edit' ? false : true;
                    //             })
                    //             ->maxLength(255),
                    //         Forms\Components\Select::make('roles')
                    //             ->label('Rol')
                    //             ->relationship('roles', 'name')
                    //             ->default([3])
                    //             ->disabled()
                    //             ->preload()
                    //             ,
                    //         Forms\Components\Select::make('owner_id')
                    //             ->disabled()
                    //             ->relationship(name: 'owner')
                    //             ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    //             ->label(__("general.Owner")),
                    //     ])

                ]),

            Forms\Components\Repeater::make('autos')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('marca')
                        ->label(__("general.Marca"))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('modelo')
                        ->label(__("general.Modelo"))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('patente')
                        ->label(__("general.Patente"))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('color')
                        ->label(__("general.Color"))
                        ->maxLength(255),
                    Forms\Components\Hidden::make('user_id')
						->disabled(function($context, $record) {
                            return $context === 'edit' && $record;
                        })
						->default(function($context, $record) {
							//dd($record);
							return  Auth::user()->id ;
						}),
					Forms\Components\Hidden::make('model')
						->disabled(function($context, $record) {
                            return $context === 'edit' && $record;
                        })
						->default(function($context) {
							return  'Owner' ;
						}),
                ])
                ->defaultItems(0)
                ->columns(2),

            Forms\Components\Repeater::make('families')
                ->label("Familiares")
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('dni')
                        ->label(__("general.DNI"))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->numeric(),

                    Forms\Components\TextInput::make('first_name')
                        ->label(__("general.FirstName"))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('last_name')
                        ->label(__("general.LastName"))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('parentage')
                        ->label(__("general.Parentesco"))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
					->numeric()
                        ->label(__("general.Phone")),

                    Toggle::make('is_menor')->label('Menor de edad'),

                ])
                ->defaultItems(0)
                ->columns(2),


            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__("general.Email"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__("general.Phone"))
                    ->numeric()
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOwners::route('/'),
        ];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Lote' => $record->lotes->first()->getNombre(),
            // 'Category' => $record->category->name,
        ];
    }


    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

}
