<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = config('app.rate_limit.max_attempts', 60);
        $decayMinutes = config('app.rate_limit.decay_minutes', 1);
        
        // Get current attempts
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60
            ], 429);
        }
        
        // Increment attempts
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
        
        return $response;
    }
    
    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        
        if ($user) {
            // Rate limit by user ID for authenticated requests
            return 'rate_limit:user:' . $user->id . ':' . $request->getPathInfo();
        }
        
        // Rate limit by IP for unauthenticated requests
        return 'rate_limit:ip:' . $request->ip() . ':' . $request->getPathInfo();
    }
}
