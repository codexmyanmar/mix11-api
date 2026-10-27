<?php

namespace App\Filament\Agent\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ReferralLinkWidget extends Widget
{
    protected static string $view = 'filament.agent.widgets.referral-link-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        return [
            'referralCode' => $user->refer_code,
            'referralUrl' => 'https://m.zvz-commerce.com/register?referral_code=' . $user->refer_code,
        ];
    }
}