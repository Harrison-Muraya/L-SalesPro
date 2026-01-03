<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogApiActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if(Auth::check()) {
            $this->logActivity($request, $response);            
        }
        return $response;
    }

    // Log activity method
    protected function logActivity(Request $request, Response $response): void
    {
       try{
        $action = $this->determineAction($request);
        $modelInfor = $this->extractModelInfo($request);

        \App\Models\ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            // 'model_type' => $modelInfor['model_type'] ? $modelInfor['type'] : 'N/A',
            // 'model_id' => $modelInfor['model_id'],
            'model_type' => 1, // Hardcoded for testing
            'model_id' => 2, // Hardcoded for testing
            'old_values' => $this->getOldValues($request),
            'new_values' => $this->getNewValues($request, $response),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'status_code' => $response->getStatusCode(),
        ]);
       } catch(\Exception $e){
        // Log the exception or handle it as needed
        Log::error('Failed to log API activity: ' . $e->getMessage());
       }
    }

    protected function determineAction(Request $request): string
    {
        // dd($request);
        $method = $request->method();
        $path = $request->path();
       

        // Parse the action from method and path
        $actions = [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete'
        ];

        $action = $actions[$method] ?? 'unknown';

        // we can add more specific action determination based on path patterns
        // Special cases
        if (str_contains($path, 'login')) {
            return 'login';
        } elseif (str_contains($path, 'logout')) {
            return 'logout';
        } elseif (str_contains($path, 'orders') && $method === 'POST') {
            return 'create_order';
        } elseif (str_contains($path, 'status') && $method === 'PUT') {
            return 'update_status';
        }

        return $action . '_' . $this->getResourceName($path);
    }

    protected function extractModelInfo(Request $request): array
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Trying to find model type and ID from path
        $modelType = null;
        $modelId = null;

        // Common patterns: /api/v1/products/123
        foreach ($segments as $index => $segment) {
            if (is_numeric($segment)) {
                $modelId = $segment;
                if (isset($segments[$index - 1])) {
                    $modelType = 'App\\Models\\' . ucfirst(rtrim($segments[$index - 1], 's'));
                }
                break;
            }
        }

        // Trying to get from request body
        if (!$modelId && $request->has('id')) {
            $modelId = $request->input('id');
        }

        return [
            'type' => $modelType,
            'id' => $modelId
        ];
    }

    protected function getOldValues(Request $request): ?array
    {
        // For updates, we would need to fetch the old model data
        // this is a placeholder implementation
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            return ['note' => 'Old values should be fetched from database'];
        }

        return null;
    }

    protected function getNewValues(Request $request, Response $response): ?array
    {
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            // Filter out sensitive data
            $data = $request->except(['password', 'password_confirmation', 'token']);
            
            return array_slice($data, 0, 10); // Limit to prevent huge logs
        }

        return null;
    }

    protected function getResourceName(string $path): string
    {
        $segments = explode('/', $path);
        
        // Find the main resource (usually after api/v1)
        foreach ($segments as $segment) {
            if ($segment !== 'api' && !str_starts_with($segment, 'v') && !is_numeric($segment)) {
                return $segment;
            }
        }
        return 'unknown';
    }
}
