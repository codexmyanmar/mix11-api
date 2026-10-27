<?php

namespace App\Filament\Shared\Resources\PaymentMethodResource\Pages;

use App\Filament\Shared\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;

    public function getTitle(): string 
    {
        return __('Payment Method List'); 
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Create New Method')), 
        ];
    }
}