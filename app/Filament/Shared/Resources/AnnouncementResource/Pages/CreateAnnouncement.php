<?php

namespace App\Filament\Shared\Resources\AnnouncementResource\Pages;

use App\Filament\Shared\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
    
    // Translate the Page Title
    public function getTitle(): string 
    {
        return __('ကြေငြာချက် အသစ်ဖန်တီးခြင်း');
    }

    // Redirect to index after creation
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}