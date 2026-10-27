<?php

namespace App\Filament\Shared\Resources\PromotionADSResource\Pages;

use App\Filament\Shared\Resources\PromotionADSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromotionADS extends ListRecords
{
    protected static string $resource = PromotionADSResource::class;

    public function getTitle(): string 
    {
        return __('Promotion List'); 
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Create New Promotion')), 
        ];
    }
}