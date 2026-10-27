<?php

namespace App\Filament\Shared\Resources\AnnouncementResource\Pages;

use App\Filament\Shared\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    // Translate the Page Title
    public function getTitle(): string 
    {
        return __('ကြေငြာချက်ကို ပြင်ဆင်ခြင်း');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('ဖျက်မည်')), // "Delete"
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}