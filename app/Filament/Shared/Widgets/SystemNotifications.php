<?php

namespace App\Filament\Shared\Widgets;

use Filament\Widgets\Widget;

class SystemNotifications extends Widget
{
    protected static string $view = 'filament-shared.widgets.system-notifications';
    
    protected int | string | array $columnSpan = 1;
    
    protected function getViewData(): array
    {
        return [
            'notifications' => auth()->user()->unreadNotifications->take(5),
        ];
    }
}