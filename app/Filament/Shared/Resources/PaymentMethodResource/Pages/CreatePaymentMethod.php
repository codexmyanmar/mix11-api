<?php

namespace App\Filament\Shared\Resources\PaymentMethodResource\Pages;

use App\Filament\Shared\Resources\PaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    public function getTitle(): string 
    {
        return __('Create New Payment Method'); 
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}