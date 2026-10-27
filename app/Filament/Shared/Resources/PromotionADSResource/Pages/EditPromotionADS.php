<?php

namespace App\Filament\Shared\Resources\PromotionADSResource\Pages;

use App\Filament\Shared\Resources\PromotionADSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromotionADS extends EditRecord
{
    protected static string $resource = PromotionADSResource::class;

    public function getTitle(): string 
    {
        return __('Edit Promotion'); 
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                 ->label(__('Delete')), 
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}