<?php

namespace App\Filament\Shared\Resources\ContactChannelResource\Pages;

use App\Filament\Shared\Resources\ContactChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactChannels extends ListRecords
{
    protected static string $resource = ContactChannelResource::class;

    public function getTitle(): string 
    {
        return __('Contact Channel List'); 
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Create New Channel')), 
        ];
    }
}