<?php

namespace App\Filament\Shared\Resources\BannerResource\Pages;

use App\Filament\Shared\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    public function getTitle(): string 
    {
        return __('ဘေဘွာများ စာရင်း'); // Banner List
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('ဘေဘွာ အသစ်ဖန်တီးမည်')), // Create New Banner
        ];
    }
}