<?php

namespace App\Filament\Shared\Resources\AnnouncementResource\Pages;

use App\Filament\Shared\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    // Translate the Page Title
    public function getTitle(): string 
    {
        return __('ကြေငြာချက်များ စာရင်း');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('ကြေငြာချက် အသစ်ဖန်တီးမည်')), // "Create New Announcement"
        ];
    }
}