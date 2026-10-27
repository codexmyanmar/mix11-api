<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\UnitHistoryLogResource\Pages;
use App\Models\UnitHistoryLog;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitHistoryLogResource extends Resource
{
    protected static ?string $model = UnitHistoryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = null;

    // Sorted after Withdraw (which is 3)
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Unit History Logs');
    }

    public static function getModelLabel(): string
    {
        return __('Unit History Log');
    }

    /**
     * Override navigation items to inject default Date Filters (Current Day)
     */
    public static function getNavigationItems(): array
    {
        $routeBase = static::getUrl('index');
        $today = Carbon::today()->format('Y-m-d');

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url($routeBase . '?tableFilters[created_at][created_from]=' . $today . '&tableFilters[created_at][created_until]=' . $today),
        ];
    }

    public static function form(Form $form): Form
    {
        // Since it's list-only, we just define a schema for View action if needed, 
        // or leave it minimal.
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => __('Topup'),
                        2 => __('Withdraw'),
                        default => $state,
                    })
                    ->disabled(),
                // Add other fields as read-only if "View" action is desired
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
      

           
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->description(fn (UnitHistoryLog $record): string => 'ID: ' . $record->user->user_id)
                    ->searchable(['name', 'id']) // Allows searching by Name OR ID
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => __('Topup'),
                        2 => __('Withdraw'),
                        default => 'Unknown',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success', // Green for Topup
                        2 => 'danger',  // Red for Withdraw
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('MMK')
                    ->sortable(),

       

                // Toggleable detail columns
                Tables\Columns\TextColumn::make('user_before_amount')
                    ->label(__('User Before'))
                    ->money('MMK')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_after_amount')
                    ->label(__('User After'))
                    ->money('MMK')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('agent_before_amount')
                    ->label(__('Agent Before'))
                    ->money('MMK')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('agent_after_amount')
                    ->label(__('Agent After'))
                    ->money('MMK')
                    ->toggleable(isToggledHiddenByDefault: true),

                     Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // 1. Type Filter
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Transaction Type'))
                    ->options([
                        1 => __('Topup'),
                        2 => __('Withdraw'),
                    ]),

                // 2. Date Filter
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
                // No Edit/Delete, maybe just View if you want
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Usually no bulk actions for logs either
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitHistoryLogs::route('/'),
        ];
    }
}