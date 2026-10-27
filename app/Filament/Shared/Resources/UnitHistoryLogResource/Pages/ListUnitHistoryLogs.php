<?php

namespace App\Filament\Shared\Resources\UnitHistoryLogResource\Pages;

use App\Filament\Shared\Resources\UnitHistoryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListUnitHistoryLogs extends ListRecords
{
    protected static string $resource = UnitHistoryLogResource::class;

    protected function getHeaderActions(): array
    {
        // Return empty array to remove "Create New" button
        return [];
    }
}