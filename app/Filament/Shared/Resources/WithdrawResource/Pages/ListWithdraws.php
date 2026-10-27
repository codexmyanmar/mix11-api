<?php

namespace App\Filament\Shared\Resources\WithdrawResource\Pages;

use App\Filament\Shared\Resources\WithdrawResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdraws extends ListRecords
{
    protected static string $resource = WithdrawResource::class;

    protected function getHeaderActions(): array
    {
        if (request()->has('tableFilters')) {
            return [];
        }

        return [
            Actions\CreateAction::make(),
        ];
    }
}