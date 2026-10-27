<?php

namespace App\Filament\Senior\Resources;

use App\Filament\Senior\Resources\MemberResource\Pages;
use App\Models\User;
use App\Models\UserBalanceTransaction; // Added
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Added
use Filament\Tables\Actions\ViewAction; // Added
use Filament\Tables\Actions\EditAction; // Added
use Filament\Tables\Actions\DeleteAction; // Added
use Filament\Tables\Actions\DeleteBulkAction; // Added
use Filament\Tables\Actions\Action; // Added
use Filament\Notifications\Notification; // Added
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
                            ->maxLength(255),

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

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super' => 'danger',
                        'master' => 'warning',
                        'agent' => 'primary',
                        'user' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('balance')
                    ->label(__('Balance'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
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
                    // Note: Changed role check to 'agent' based on Senior hierarchy. 
                    // Change 'agent' to 'super' if you strictly meant Super admins.
                    ->visible(fn (User $record) => $record->hasRole('master')) 
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        
                        Forms\Components\Textarea::make('note')
                            ->label(__('Note')),
                    ])
                    ->action(function (User $record, array $data) {
                        $admin = auth()->user();
                        $amount = $data['amount'];
                        $note = $data['note'] ?? '';

                        if ($admin->balance < $amount) {
                            Notification::make()
                                ->title(__('Insufficient Balance'))
                                ->body(__('You do not have enough balance to perform this deposit.'))
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $admin, $amount, $note) {
                            // --- A. Handle Admin (Sender) ---
                            $admin->decrement('balance', $amount);
                            // Assuming withdraw_able exists on model as per first file
                            // $admin->decrement('withdraw_able', $amount); 

                            UserBalanceTransaction::create([
                                'user_id' => $admin->id,
                                'type' => UserBalanceTransaction::TYPE_REDUCE, 
                                'amount' => $amount,
                                'reason' => "{$record->name} (ID: " . ($record->user_id ?? '-') . ") သို့ ငွေလွှဲပြောင်းပေးပို့ခြင်း",
                                'comment' => $note,
                                'status' => UserBalanceTransaction::STATUS_COMPLETED,
                                'which_type' => 'deposit_withdraw',
                            ]);

                            // --- B. Handle Target User (Receiver) ---
                            $record->increment('balance', $amount);
                            // $record->increment('withdraw_able', $amount);

                            UserBalanceTransaction::create([
                                'user_id' => $record->id,
                                'type' => UserBalanceTransaction::TYPE_ADD,
                                'amount' => $amount,
                                'reason' => "{$admin->name} (ID: " . ($admin->user_id ?? '-') . ") ထံမှ ငွေဖြည့်သွင်းမှု လက်ခံရရှိခြင်း",
                                'comment' => $note,
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
                    ->visible(fn (User $record) => $record->hasRole('agent')) 
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01),

                        Forms\Components\Textarea::make('note')
                            ->label(__('Note')),
                    ])
                    ->action(function (User $record, array $data) {
                        $admin = auth()->user();
                        $amount = $data['amount'];
                        $note = $data['note'] ?? '';

                        if ($record->balance < $amount) {
                            Notification::make()
                                ->title(__('Insufficient Balance'))
                                ->body(__("The user :name does not have enough balance.", ['name' => $record->name]))
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $admin, $amount, $note) {
                            // --- A. Handle Target User (Sender) ---
                            $record->decrement('balance', $amount);
                            // $record->decrement('withdraw_able', $amount);

                            UserBalanceTransaction::create([
                                'user_id' => $record->id,
                                'type' => UserBalanceTransaction::TYPE_REDUCE, 
                                'amount' => $amount,
                                'reason' => "{$admin->name} (ID: " . ($admin->user_id ?? '-') . ") မှ ငွေထုတ်ယူခြင်း",
                                'comment' => $note,
                                'status' => UserBalanceTransaction::STATUS_COMPLETED,
                                'which_type' => 'deposit_withdraw',
                            ]);

                            // --- B. Handle Admin (Receiver) ---
                            $admin->increment('balance', $amount);
                            // $admin->increment('withdraw_able', $amount);

                            UserBalanceTransaction::create([
                                'user_id' => $admin->id,
                                'type' => UserBalanceTransaction::TYPE_ADD,
                                'amount' => $amount,
                                'reason' => "{$record->name} (ID: " . ($record->user_id ?? '-') . ") ထံမှ ငွေပြန်လည် လက်ခံရရှိခြင်း",
                                'comment' => $note,
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
                    ->visible(fn (User $record) => $record->hasRole('master'))
                    ->requiresConfirmation()
                    ->modalHeading(__('Change User Status'))
                    ->action(fn (User $record) => $record->update(['status' => !$record->status])),

                EditAction::make()
                    ->label(__('Edit'))
                    ->visible(fn (User $record) => $record->hasRole('master')),

                DeleteAction::make()
                    ->label(__('Delete'))
                    ->visible(fn (User $record) => $record->hasRole('master')),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(__('Delete')),
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