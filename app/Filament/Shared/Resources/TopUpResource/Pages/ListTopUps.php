<?php

namespace App\Filament\Shared\Resources\TopUpResource\Pages;

use App\Filament\Shared\Resources\TopUpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopUps extends ListRecords
{
    protected static string $resource = TopUpResource::class;

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