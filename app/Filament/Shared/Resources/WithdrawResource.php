<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\WithdrawResource\Pages;
use App\Models\PaymentMethod;
use App\Models\Withdraw;
use App\Models\User;
use App\Models\UnitHistoryLog;
use App\Models\UserBalanceTransaction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawResource extends Resource
{
    protected static ?string $model = Withdraw::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 3;
    
    public static function getNavigationLabel(): string
    {
        return __('Withdraw Requests');
    }

    public static function getModelLabel(): string
    {
        return __('Withdraw Request');
    }

    public static function getNavigationItems(): array
    {
        $routeBase = static::getUrl('index');
        $today = Carbon::today()->format('Y-m-d');

        return [
            NavigationItem::make(__('Pending Withdraw'))
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-clock')
                ->badge(Withdraw::where('status', 0)->count())
                ->sort(1)
                ->url($routeBase . '?tableFilters[status][value]=0&tableFilters[created_at][created_from]=' . $today . '&tableFilters[created_at][created_until]=' . $today),

            NavigationItem::make(__('Complete Withdraw'))
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-check-circle')
                ->sort(2)
                ->url($routeBase . '?tableFilters[status][value]=1&tableFilters[created_at][created_from]=' . $today . '&tableFilters[created_at][created_until]=' . $today),
                
            NavigationItem::make(__('Withdraw Do'))
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn () => request()->url() === $routeBase && !request()->has('tableFilters'))
                ->sort(3)
                ->url($routeBase),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Withdrawal Details'))
                    ->schema([
                        // 1. User Selection (Updated to be Live)
                        Forms\Components\Select::make('user_id')
                            ->label(__('User'))
                            ->options(fn () => User::role('user')->pluck('name', 'id')) 
                            ->getSearchResultsUsing(fn (string $search) => User::role('user')
                                ->where(function($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                          ->orWhere('email', 'like', "%{$search}%")
                                          ->orWhere('id', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live() // Make reactive
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    $set('user_balance', $user ? $user->balance : 0);
                                } else {
                                    $set('user_balance', 0);
                                }
                            })
                            ->required(),

                        // 2. New Balance Display Field
                        Forms\Components\TextInput::make('user_balance')
                            ->label(__('Current Balance'))
                            ->disabled() // Read-only
                            ->dehydrated(false) // Do not save to DB
                            ->numeric()
                            ->prefix('K'),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->prefix('K')
                            ->required(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label(__('Bank Name'))
                            ->placeholder('e.g., KBZ Pay, Wave Money')
                            ->required(),

                        Forms\Components\TextInput::make('account_name')
                            ->label(__('Account Name'))
                            ->required(),

                        Forms\Components\TextInput::make('account_no')
                            ->label(__('Account Number'))
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                0 => __('Pending'),
                                1 => __('Complete'),
                                2 => __('Reject'),
                            ])
                            ->default(0)
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('Note / Reason'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('MMK')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Bank Name')),
                
                Tables\Columns\TextColumn::make('account_no')
                    ->label(__('Account No')),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('Pending'),
                        '1' => __('Complete'),
                        '2' => __('Reject'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'warning',
                        '1' => 'success',
                        '2' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        0 => __('Pending'),
                        1 => __('Complete'),
                        2 => __('Reject'),
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('From Date'))
                            ->default(now()),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('To Date'))
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('update_status')
                    ->label(__('Change Status'))
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    // Only visible for Pending
                    ->visible(fn (Withdraw $record) => $record->status == 0) 
                    ->mountUsing(fn (Forms\ComponentContainer $form, Withdraw $record) => $form->fill([
                        'user_name' => $record->user->name ?? 'Unknown',
                        'amount_display' => number_format($record->amount) . ' MMK',
                        'status' => $record->status,
                        'reason' => $record->reason,
                    ]))
                    ->form([
                        Forms\Components\TextInput::make('user_name')
                            ->label(__('User Requesting'))
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('amount_display')
                            ->label(__('Amount'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('New Status'))
                            ->options([
                                0 => __('Pending'),
                                1 => __('Complete'),
                                2 => __('Reject'),
                            ])
                            ->required()
                            ->default(0),
                        
                        Forms\Components\Textarea::make('reason')
                            ->label(__('Reason / Note')),
                    ])
                    ->action(function (Withdraw $record, array $data): void {
                        $newStatus = (int) $data['status'];
                        $amount = $record->amount;

                        // Proceed if status changed
                        if ($newStatus !== 0) {
                            
                            $agent = auth()->user();
                            $targetUser = User::find($record->user_id);

                            DB::transaction(function () use ($record, $data, $agent, $targetUser, $amount, $newStatus) {
                                
                                if ($newStatus === 1) { 
                                    // *** STATUS: COMPLETE ***
                                    // User balance was ALREADY reduced during Pending creation.
                                    // Agent receives the units (Add to Agent).

                                    // 1. Log History
                                    UnitHistoryLog::create([
                                        'agent_id' => $agent->id,
                                        'user_id' => $targetUser->id,
                                        'amount' => $amount,
                                        'user_before_amount' => $targetUser->balance,
                                        'user_after_amount' => $targetUser->balance, // No change now, already deducted
                                        'agent_before_amount' => $agent->balance,
                                        'agent_after_amount' => $agent->balance + $amount,
                                        'type' => 2, // 2 = Withdraw
                                    ]);

                                    // 2. Update Agent Balance (Add)
                                    $agent->increment('balance', $amount);
                                    // $agent->increment('withdraw_able', $amount); 

                                    UserBalanceTransaction::create([
                                        'user_id' => $agent->id,
                                        'type' => 1, // Add
                                        'amount' => $amount,
                                        'reason' => "Withdraw processed for {$targetUser->name} (ID: {$targetUser->id})",
                                        'comment' => $data['reason'] ?? 'Withdraw Completed',
                                        'status' => 2,
                                        'which_type' => 'deposit_withdraw',
                                    ]);

                                    Notification::make()
                                        ->title(__('Withdraw Approved'))
                                        ->success()
                                        ->send();

                                } elseif ($newStatus === 2) {
                                    // *** STATUS: REJECT ***
                                    // Refund the user (Add back).

                                    // 1. Update User Balance (Refund)
                                    $targetUser->increment('balance', $amount);
                                    $targetUser->increment('withdraw_able', $amount); 

                                    UserBalanceTransaction::create([
                                        'user_id' => $targetUser->id,
                                        'type' => 1, // Add
                                        'amount' => $amount,
                                        'reason' => "Withdraw Rejected Refund (ID: {$record->id})",
                                        'comment' => $data['reason'] ?? 'Withdraw Rejected',
                                        'status' => 2,
                                        'which_type' => 'deposit_withdraw',
                                    ]);

                                    Notification::make()
                                        ->title(__('Withdraw Rejected & Refunded'))
                                        ->warning()
                                        ->send();
                                }

                                // Update Record
                                $record->update([
                                    'status' => $newStatus,
                                    'reason' => $data['reason'] ?? $record->reason,
                                ]);
                            });
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdraws::route('/'),
            'create' => Pages\CreateWithdraw::route('/create'),
        ];
    }
}