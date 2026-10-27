<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    // 1. Translate Navigation Label
    public static function getNavigationLabel(): string
    {
        return __('Admin Accounts'); // Ensure you add "Admin Accounts" to your JSON
    }

    // 2. Translate Model Label (Singular)
    public static function getModelLabel(): string
    {
        return __('Admin'); // Add "Admin" to JSON
    }

    // 3. Translate Model Label (Plural)
    public static function getPluralModelLabel(): string
    {
        return __('Admins'); // Add "Admins" to JSON
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Admin Details')) // Add "Admin Details" to JSON
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name')) // Uses "Name" from your JSON
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Email Address')) // Uses "Email Address" from your JSON
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Password')) // Uses "Password" from your JSON
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('status')
                            ->label(__('Active')) // Uses "Active Account" from your JSON
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email Address'))
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('Active')) // Add "Active" to JSON
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at')) // Uses "Created at" from your JSON
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('Status')) // Add "Status" to JSON
                    ->boolean()
                    ->trueLabel(__('Active Users')) // Add "Active Users" to JSON
                    ->falseLabel(__('Banned Users')) // Add "Banned Users" to JSON
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit')), // Uses "Edit" from JSON
                
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete')), // Uses "Delete" from JSON
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('Delete')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return $query->whereHas('roles', fn ($q) => $q->where('name', 'admin'));
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