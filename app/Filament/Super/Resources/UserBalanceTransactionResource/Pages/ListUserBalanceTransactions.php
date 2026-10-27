<?php

namespace App\Filament\Super\Resources\UserBalanceTransactionResource\Pages;

use App\Filament\Super\Resources\UserBalanceTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListUserBalanceTransactions extends ListRecords
{
    protected static string $resource = UserBalanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create Action here
        ];
    }
}