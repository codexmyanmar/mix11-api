<?php

namespace App\Filament\Shared\Resources\ContactChannelResource\Pages;

use App\Filament\Shared\Resources\ContactChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactChannel extends EditRecord
{
    protected static string $resource = ContactChannelResource::class;

    public function getTitle(): string 
    {
        return __('Edit Channel'); 
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