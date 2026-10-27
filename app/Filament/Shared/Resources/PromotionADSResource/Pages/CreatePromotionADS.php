<?php

namespace App\Filament\Shared\Resources\PromotionADSResource\Pages;

use App\Filament\Shared\Resources\PromotionADSResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromotionADS extends CreateRecord
{
    protected static string $resource = PromotionADSResource::class;

    public function getTitle(): string 
    {
        return __('Create New Promotion'); 
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}