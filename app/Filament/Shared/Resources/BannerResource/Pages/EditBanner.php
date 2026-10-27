<?php

namespace App\Filament\Shared\Resources\BannerResource\Pages;

use App\Filament\Shared\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    public function getTitle(): string 
    {
        return __('ဘေဘွာကို ပြင်ဆင်ခြင်း'); // Edit Banner
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                 ->label(__('ဖျက်မည်')), // Delete
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}