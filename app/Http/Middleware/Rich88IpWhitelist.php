
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Rich88IpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = [
            '18.180.74.201',
            '60.251.26.1',
            '3.114.232.213'
        ];
        
        $clientIp = $request->ip();
        
        // Check if IP is in whitelist
        if (!in_array($clientIp, $allowedIps)) {
            Log::warning('Rich88 API accessed from unauthorized IP', [
                'ip' => $clientIp,
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'code' => 403,
                'msg' => 'Forbidden - IP not whitelisted'
            ], 403);
        }
        
        Log::info('Rich88 API accessed from authorized IP', ['ip' => $clientIp]);
        
        return $next($request);
    }
}