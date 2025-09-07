<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();
        
        // Log incoming request
        $this->logRequest($request, $requestId);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
        
        // Log response
        $this->logResponse($request, $response, $requestId, $duration);
        
        return $response;
    }
    
    /**
     * Log incoming request details
     */
    protected function logRequest(Request $request, string $requestId): void
    {
        $user = Auth::user();
        
        $logData = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : null,
            'company_id' => $user ? $user->company_id : null,
            'headers' => $this->getFilteredHeaders($request),
            'query_params' => $request->query->all(),
            'timestamp' => now()->toISOString()
        ];
        
        // Only log request body for non-GET requests and exclude sensitive data
        if (!$request->isMethod('GET')) {
            $logData['request_body'] = $this->getFilteredRequestBody($request);
        }
        
        Log::info('API Request', $logData);
    }
    
    /**
     * Log response details
     */
    protected function logResponse(Request $request, Response $response, string $requestId, float $duration): void
    {
        $logData = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
            'timestamp' => now()->toISOString()
        ];
        
        // Log response body for error responses
        if ($response->getStatusCode() >= 400) {
            $logData['response_body'] = $this->getFilteredResponseBody($response);
        }
        
        $logLevel = $this->getLogLevel($response->getStatusCode());
        Log::log($logLevel, 'API Response', $logData);
        
        // Log slow requests
        if ($duration > 1000) { // More than 1 second
            Log::warning('Slow API Request', [
                'request_id' => $requestId,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'duration_ms' => $duration
            ]);
        }
    }
    
    /**
     * Get filtered headers (exclude sensitive information)
     */
    protected function getFilteredHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        
        // Remove sensitive headers
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }
        
        return $headers;
    }
    
    /**
     * Get filtered request body (exclude sensitive information)
     */
    protected function getFilteredRequestBody(Request $request): array
    {
        $body = $request->all();
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '[FILTERED]';
            }
        }
        
        return $body;
    }
    
    /**
     * Get filtered response body (limit size and exclude sensitive data)
     */
    protected function getFilteredResponseBody(Response $response): string
    {
        $content = $response->getContent();
        
        // Limit response body size in logs
        if (strlen($content) > 1000) {
            $content = substr($content, 0, 1000) . '... [TRUNCATED]';
        }
        
        return $content;
    }
    
    /**
     * Determine log level based on status code
     */
    protected function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }
}
