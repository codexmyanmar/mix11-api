<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\TopUpResource\Pages;
use App\Models\PaymentMethod;
use App\Models\TopUp;
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

class TopUpResource extends Resource
{
    protected static ?string $model = TopUp::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;
    
    public static function getNavigationLabel(): string
    {
        return __('Deposit Requests');
    }

    public static function getModelLabel(): string
    {
        return __('Deposit Request');
    }

    public static function getNavigationItems(): array
    {
        $routeBase = static::getUrl('index');
        $today = Carbon::today()->format('Y-m-d');

        return [
            NavigationItem::make(__('Pending Top Up'))
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-clock')
                ->badge(TopUp::where('status', 0)->count())
                ->sort(1)
                // Updated URL parameters to support created_from and created_until
                ->url($routeBase . '?tableFilters[status][value]=0&tableFilters[created_at][created_from]=' . $today . '&tableFilters[created_at][created_until]=' . $today),

            NavigationItem::make(__('Complete Top Up'))
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-check-circle')
                ->sort(2)
                // Updated URL parameters to support created_from and created_until
                ->url($routeBase . '?tableFilters[status][value]=1&tableFilters[created_at][created_from]=' . $today . '&tableFilters[created_at][created_until]=' . $today),
                
            NavigationItem::make(__('Topup Do'))
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
                Forms\Components\Section::make(__('Deposit Details'))
                    ->schema([
                        // RESTORED: Fully editable for Create Page
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
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->prefix('K')
                            ->required(),

                        // RESTORED: Logic for auto-filling payment details
                        Forms\Components\Select::make('payment_method_id')
                            ->label(__('Select Payment Method'))
                            ->options(PaymentMethod::where('user_id', Auth::id())->where('status', 1)->pluck('bank_name', 'id'))
                            ->live() 
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $method = PaymentMethod::find($state);
                                    if ($method) {
                                        $set('payment_method', $method->bank_name);
                                        $set('account_name', $method->name);
                                        $set('account_no', $method->account_no);
                                    }
                                }
                            })
                            ->required(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label(__('Bank Name'))
                            ->readOnly()
                            ->required(),

                        Forms\Components\TextInput::make('account_name')
                            ->label(__('Account Name'))
                            ->readOnly()
                            ->required(),

                        Forms\Components\TextInput::make('account_no')
                            ->label(__('Account Number'))
                            ->readOnly()
                            ->required(),

                        Forms\Components\TextInput::make('latest_six_number')
                            ->label(__('Last 6 Digits'))
                            ->maxLength(6),

                        Forms\Components\FileUpload::make('payemnt_slip') 
                            ->label(__('Payment Slip'))
                            ->image()
                            ->directory('topup-slips')
                            ->columnSpanFull(),
                        
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

                Tables\Columns\ImageColumn::make('payemnt_slip')
                    ->label(__('Slip'))
                    ->circular(),

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
                // Custom Action to Change Status with Logic
                Tables\Actions\Action::make('update_status')
                    ->label(__('Change Status'))
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    // Visible ONLY if status is Pending (0)
                    ->visible(fn (TopUp $record) => $record->status == 0) 
                    ->mountUsing(fn (Forms\ComponentContainer $form, TopUp $record) => $form->fill([
                        'user_name' => $record->user->name ?? 'Unknown',
                        'amount_display' => number_format($record->amount) . ' MMK',
                        'status' => $record->status,
                        'reason' => $record->reason,
                    ]))
                    ->form([
                        // Read-only info fields for context in the modal
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
                    ->action(function (TopUp $record, array $data): void {
                        $oldStatus = $record->status;
                        $newStatus = (int) $data['status'];
                        $amount = $record->amount;

                        // Only process logic if status is changing to Complete (1) from Pending
                        if ($newStatus === 1 && $oldStatus !== 1) {
                            
                            $agent = auth()->user();
                            $targetUser = User::find($record->user_id);

                            // Check Agent Balance
                            if ($agent->balance < $amount) {
                                Notification::make()
                                    ->title(__('Insufficient Balance'))
                                    ->body(__('You do not have enough balance to approve this deposit.'))
                                    ->danger()
                                    ->send();
                                
                                // Stop execution
                                return;
                            }

                            // Perform Transaction
                            DB::transaction(function () use ($record, $data, $agent, $targetUser, $amount, $newStatus) {
                                // 1. Log History
                                UnitHistoryLog::create([
                                    'agent_id' => $agent->id,
                                    'user_id' => $targetUser->id,
                                    'amount' => $amount,
                                    'user_before_amount' => $targetUser->balance,
                                    'user_after_amount' => $targetUser->balance + $amount,
                                    'agent_before_amount' => $agent->balance,
                                    'agent_after_amount' => $agent->balance - $amount,
                                    'type' => 1, // Topup
                                ]);

                                // 2. Update Agent Balance (Reduce)
                                $agent->decrement('balance', $amount);
                                // $agent->decrement('withdraw_able', $amount); // Optional

                                UserBalanceTransaction::create([
                                    'user_id' => $agent->id,
                                    'type' => 2,
                                    'amount' => $amount,
                                    'reason' => "Transfer to {$targetUser->name} (ID: {$targetUser->id})",
                                    'comment' => $data['reason'] ?? 'Deposit Approved',
                                    'status' => 2,
                                    'which_type' => 'deposit_withdraw',
                                ]);

                                // 3. Update User Balance (Add)
                                $targetUser->increment('balance', $amount);
                                // $targetUser->increment('withdraw_able', $amount); // Optional

                                UserBalanceTransaction::create([
                                    'user_id' => $targetUser->id,
                                    'type' => 1,
                                    'amount' => $amount,
                                    'reason' => "Deposit from {$agent->name} (ID: {$agent->id})",
                                    'comment' => $data['reason'] ?? 'Deposit Approved',
                                    'status' => 2,
                                    'which_type' => 'deposit_withdraw',
                                ]);

                                // 4. Update TopUp Record
                                $record->update([
                                    'status' => $newStatus,
                                    'reason' => $data['reason'] ?? $record->reason,
                                ]);
                            });

                            Notification::make()
                                ->title(__('Deposit Approved'))
                                ->success()
                                ->send();

                        } else {
                            // Just update status (e.g., Reject or back to Pending)
                            $record->update([
                                'status' => $newStatus,
                                'reason' => $data['reason'] ?? $record->reason,
                            ]);
                            
                            Notification::make()
                                ->title(__('Status Updated'))
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopUps::route('/'),
            'create' => Pages\CreateTopUp::route('/create'),
            // No edit page route
        ];
    }
}