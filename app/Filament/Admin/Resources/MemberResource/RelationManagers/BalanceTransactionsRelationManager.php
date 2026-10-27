<?php

namespace App\Filament\Admin\Resources\MemberResource\RelationManagers;

use App\Models\UserBalanceTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BalanceTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'balanceTransactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reason')
                    ->label(__('Reason'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

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
                    ->prefix('฿') // Added Thai Baht symbol here as well
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
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('From Date'))
                            ->default(now()), // Default to Today
                            
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('To Date'))
                            ->default(now()), // Default to Today
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                 
                        if ($data['created_from'] ?? null) {
                            $indicators[] = __('From Date') . ': ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                 
                        if ($data['created_until'] ?? null) {
                            $indicators[] = __('To Date') . ': ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                 
                        return $indicators;
                    }),
            ])
            ->headerActions([
                // 
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}