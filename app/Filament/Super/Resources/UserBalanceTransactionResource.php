<?php

namespace App\Filament\Super\Resources;

use App\Filament\Super\Resources\UserBalanceTransactionResource\Pages;
use App\Models\UserBalanceTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class UserBalanceTransactionResource extends Resource
{
    protected static ?string $model = UserBalanceTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('My Transaction Logs');
    }

    public static function getModelLabel(): string
    {
        return __('My Transaction Logs');
    }

    // 1. FILTER QUERY TO SHOW ONLY CURRENT USER'S DATA
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    // DISABLE CREATE
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // This form is used for the "View" page
        return $form
            ->schema([
                Forms\Components\Section::make(__('Transaction Details'))
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label(__('User Name')),
                        
                        Forms\Components\TextInput::make('user.user_id')
                            ->label(__('User ID')),

                        Forms\Components\TextInput::make('type')
                            ->label(__('Type'))
                            ->formatStateUsing(fn ($state) => match ($state) {
                                1 => __('Deposit'),
                                2 => __('Withdraw'),
                                3 => __('Freeze'),
                                4 => __('Game'),
                                default => __('Other'),
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->prefix('$'),

                        Forms\Components\TextInput::make('reason')
                            ->label(__('Reason')),

                        Forms\Components\TextInput::make('status')
                            ->label(__('Status'))
                            ->formatStateUsing(fn ($state) => match ($state) {
                                1 => __('Pending'),
                                2 => __('Completed'),
                                3 => __('Failed'),
                                default => __('Unknown'),
                            }),

                        Forms\Components\Placeholder::make('created_at')
                            ->label(__('Date'))
                            ->content(fn ($record) => $record->created_at->toDateTimeString()),

                        Forms\Components\Textarea::make('comment')
                            ->label(__('Comment'))
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                // You can remove the User Column if you only see your own data, 
                // but I kept it here just in case you want to confirm it's yours.
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->description(fn (UserBalanceTransaction $record) => $record->user?->user_id)
                    ->searchable(['name', 'user_id'])
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default since it's always you

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        UserBalanceTransaction::TYPE_ADD => __('Deposit'),
                        UserBalanceTransaction::TYPE_REDUCE => __('Withdraw'),
                        UserBalanceTransaction::TYPE_FREEZE => __('Freeze'),
                        UserBalanceTransaction::TYPE_GAME_SYNC => __('Game'),
                        default => __('Other'),
                    })
                    ->color(fn (int $state): string => match ($state) {
                        UserBalanceTransaction::TYPE_ADD => 'success',
                        UserBalanceTransaction::TYPE_REDUCE => 'danger',
                        UserBalanceTransaction::TYPE_FREEZE => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->color(fn ($record) => $record->type === UserBalanceTransaction::TYPE_ADD ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                         UserBalanceTransaction::STATUS_PENDING => __('Pending'),
                         UserBalanceTransaction::STATUS_COMPLETED => __('Success'),
                         UserBalanceTransaction::STATUS_FAILED => __('Failed'),
                         default => __('Unknown'),
                    })
                    ->color(fn (int $state): string => match ($state) {
                        UserBalanceTransaction::STATUS_COMPLETED => 'success',
                        UserBalanceTransaction::STATUS_FAILED => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->label(__('Date'))
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label(__('Filter Date'))
                            ->default(now()) 
                            ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['date']) {
                            return null;
                        }
                        return __('Date') . ': ' . \Carbon\Carbon::parse($data['date'])->toFormattedDateString();
                    }),
                
                // Add Filter by Type
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        UserBalanceTransaction::TYPE_ADD => __('Deposit'),
                        UserBalanceTransaction::TYPE_REDUCE => __('Withdraw'),
                        UserBalanceTransaction::TYPE_GAME_SYNC => __('Game'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Only View Action
            ])
            ->bulkActions([
                // No bulk delete allowed
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUserBalanceTransactions::route('/'),
            // Only Index and View pages
            'view' => Pages\ViewUserBalanceTransaction::route('/{record}'),
        ];
    }
}