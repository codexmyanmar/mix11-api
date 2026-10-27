<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MemberResource\Pages;
use App\Models\User;
use App\Models\UserBalanceTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action; 
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\MemberResource\RelationManagers\BalanceTransactionsRelationManager;
use Filament\Infolists;
use Filament\Infolists\Infolist;


class MemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $slug = 'members'; 

    public static function getNavigationLabel(): string
    {
        return __('User Accounts');
    }

    public static function getModelLabel(): string
    {
        return __('Member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Members');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Account Info'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Email Address'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->visibleOn(['create', 'edit']), 

                        Forms\Components\Toggle::make('status')
                            ->label(__('Active Account'))
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make(__('Wallet Info'))
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label(__('Balance'))
                            ->numeric()
                            ->prefix('$') 
                            ->default(0)
                            ->readOnly(), 
                            
                        Forms\Components\TextInput::make('commission')
                            ->label(__('Commission'))
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        Forms\Components\TextInput::make('user_id')
                            ->label(__('User ID'))
                            ->readOnly(),

                         Forms\Components\TextInput::make('last_login_ip')
                         ->label(__('Last Login IP'))
                         ->readOnly(),
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

                Tables\Columns\TextColumn::make('user_id')
                    ->label(__('User ID 111'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label(__('Balance'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                  Tables\Columns\TextColumn::make('email')  // Hidden by default but searchable
                ->label(__('Email'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('phone')  // Hidden by default but searchable
                ->label(__('Phone'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                // 1. View Action 
                ViewAction::make()
                    ->label(__('View')),

                // 2. Deposit Action
                Action::make('deposit')
                    ->label(__('Deposit'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (User $record) => $record->hasRole('super')) // Optional: adjust permission
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\TextInput::make('reason')
                            ->label(__('Reason'))
                            ->default('Manual Deposit')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            // 1. Update User Balance & Withdrawable
                            $record->increment('balance', $data['amount']);
                            $record->increment('withdraw_able', $data['amount']);

                            // 2. Create Transaction Log
                            UserBalanceTransaction::create([
                                'user_id' => $record->id,
                                'type' => UserBalanceTransaction::TYPE_ADD, // 1
                                'amount' => $data['amount'],
                                'reason' => $data['reason'],
                                'status' => UserBalanceTransaction::STATUS_COMPLETED,
                                'which_type' => 'deposit_withdraw',
                            ]);
                        });

                        Notification::make()
                            ->title(__('Deposit Successful'))
                            ->success()
                            ->send();
                    }),

                // 3. Withdraw Action
                Action::make('withdraw')
                    ->label(__('Withdraw'))
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->visible(fn (User $record) => $record->hasRole('super')) // Optional: adjust permission
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\TextInput::make('reason')
                            ->label(__('Reason'))
                            ->default('Manual Withdraw')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        if ($record->balance < $data['amount']) {
                            Notification::make()
                                ->title(__('Insufficient Balance'))
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $data) {
                            // 1. Update User Balance & Withdrawable
                            $record->decrement('balance', $data['amount']);
                            // Ensure we don't drop below zero for withdrawable if logic requires, 
                            // otherwise simple decrement matches your request.
                            $record->decrement('withdraw_able', $data['amount']);

                            // 2. Create Transaction Log
                            UserBalanceTransaction::create([
                                'user_id' => $record->id,
                                'type' => UserBalanceTransaction::TYPE_REDUCE, // 2
                                'amount' => $data['amount'],
                                'reason' => $data['reason'],
                                'status' => UserBalanceTransaction::STATUS_COMPLETED,
                                'which_type' => 'deposit_withdraw',
                            ]);
                        });

                        Notification::make()
                            ->title(__('Withdraw Successful'))
                            ->success()
                            ->send();
                    }),

                // 4. Activate/Deactivate Action
                Action::make('toggle_status')
                    ->label(fn (User $record) => $record->status ? __('Deactivate') : __('Activate'))
                    ->icon(fn (User $record) => $record->status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->status ? 'danger' : 'success')
                    ->visible(fn (User $record) => $record->hasRole('super'))
                    ->requiresConfirmation()
                    ->modalHeading(__('Change User Status'))
                    ->action(fn (User $record) => $record->update(['status' => !$record->status])),

                // 5. Edit Action
                EditAction::make()
                    ->label(__('Edit'))
                    ->visible(fn (User $record) => $record->hasRole('super')),

                // 6. Delete Action
                DeleteAction::make()
                    ->label(__('Delete'))
                    ->visible(fn (User $record) => $record->hasRole('super')),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(__('Delete')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BalanceTransactionsRelationManager::class,
        ];
    }
    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'), 
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Account Info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('Name')),

                        Infolists\Components\TextEntry::make('email')
                            ->label(__('Email Address'))
                            ->icon('heroicon-m-envelope'),

                        Infolists\Components\IconEntry::make('status')
                            ->label(__('Active Account'))
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])->columns(2),

                Infolists\Components\Section::make(__('Wallet Info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('balance')
                            ->label(__('Balance'))
                            ->prefix('฿') ,

                        Infolists\Components\TextEntry::make('commission')
                            ->label(__('Commission')),

                        Infolists\Components\TextEntry::make('user_id')
                            ->label(__('User ID'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('last_login_ip')
                            ->label(__('Last Login IP')),
                    ])->columns(2),
            ]);
    }
    

}