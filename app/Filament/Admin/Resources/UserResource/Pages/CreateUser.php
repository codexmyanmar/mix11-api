<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $prefix = env('APP_USER_PREFIX', 'ppa');

        do {
            $generatedId = $prefix . 'admin' . mt_rand(100000, 999999);
        } while (User::where('user_id', $generatedId)->exists());
        $data['user_id'] = $generatedId;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('admin'); 
    }
}