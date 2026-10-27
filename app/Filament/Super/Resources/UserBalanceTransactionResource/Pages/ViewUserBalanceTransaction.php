<?php

namespace App\Filament\Super\Resources\UserBalanceTransactionResource\Pages;

use App\Filament\Super\Resources\UserBalanceTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUserBalanceTransaction extends ViewRecord
{
    protected static string $resource = UserBalanceTransactionResource::class;
    
    // Remove Edit Action
    protected function getHeaderActions(): array
    {
        return [];
    }
}