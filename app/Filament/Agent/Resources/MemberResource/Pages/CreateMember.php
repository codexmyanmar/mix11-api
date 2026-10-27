<?php

namespace App\Filament\Agent\Resources\MemberResource\Pages;

use App\Filament\Agent\Resources\MemberResource;
use App\Models\ReferLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate Unique User ID
        $prefix = env('APP_USER_PREFIX', 'ppa');
        do {
            $generatedId = $prefix . 'user' . mt_rand(100000, 999999);
        } while (User::where('user_id', $generatedId)->exists());
        $data['user_id'] = $generatedId;

        // Generate Unique Referral Code (6 character alphanumeric)
        do {
            $referCode = $this->generateUniqueReferCode();
        } while (User::where('refer_code', $referCode)->exists());
        $data['refer_code'] = $referCode;

        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Assign Role as 'user' (As per your requirement)
        $this->record->assignRole('user');

        // 2. Create ReferLog
        ReferLog::create([
            'referrer_user_id' => Auth::id(), // The Agent creating this user
            'referred_user_id' => $this->record->id, // The new user
        ]);
    }

    /**
     * Generate a unique 6-character alphanumeric code
     * 
     * @return string
     */
    private function generateUniqueReferCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $length = 6;
        
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, strlen($characters) - 1);
            $code .= $characters[$randomIndex];
        }
        
        return $code;
    }

    // Optional: Redirect back to the list after creation
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}