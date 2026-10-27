<?php

namespace App\Filament\Shared\Resources\PaymentMethodResource\Pages;

use App\Filament\Shared\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    public function getTitle(): string 
    {
        return __('Edit Payment Method'); 
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