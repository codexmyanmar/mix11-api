<?php

namespace App\Filament\Shared\Resources\BannerResource\Pages;

use App\Filament\Shared\Resources\BannerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

    public function getTitle(): string 
    {
        return __('ဘေဘွာ အသစ်ဖန်တီးခြင်း'); // Create Banner
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}