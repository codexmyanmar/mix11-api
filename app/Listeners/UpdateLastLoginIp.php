<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class UpdateLastLoginIp
{
    public function handle(Login $event): void
    {
        // Save the IP address when user logs in
        $event->user->update([
            'last_login_ip' => Request::ip()
        ]);
    }
}