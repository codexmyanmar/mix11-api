<?php

namespace App\Filament\Shared\Resources\ContactChannelResource\Pages;

use App\Filament\Shared\Resources\ContactChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactChannel extends CreateRecord
{
    protected static string $resource = ContactChannelResource::class;

    public function getTitle(): string 
    {
        return __('Create New Channel'); 
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}