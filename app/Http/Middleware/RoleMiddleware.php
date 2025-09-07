<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = auth()->user();



        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is inactive'
            ], 403);
        }

        // Parse multiple roles separated by pipe
        $allowedRoles = array_map('trim', explode('|', $roles));
        $hasAccess = false;

        foreach ($allowedRoles as $role) {
            switch ($role) {
                case 'super_admin':
                    if ($user->isSuperAdmin()) {
                        $hasAccess = true;
                    }
                    break;

                case 'admin_company':
                    if ($user->isCompanyAdmin() || $user->isSuperAdmin()) {
                        $hasAccess = true;
                    }
                    break;

                case 'employee':
                    if ($user->isEmployee() || $user->isCompanyAdmin() || $user->isSuperAdmin()) {
                        $hasAccess = true;
                    }
                    break;

                case 'company_member':
                    // Allow company admin and employees of the same company
                    if ($user->isSuperAdmin()) {
                        $hasAccess = true;
                    } elseif ($user->company_id) {
                        $hasAccess = true;
                    }
                    break;

                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid role specified: ' . $role
                    ], 500);
            }

            if ($hasAccess) {
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Required roles: ' . $roles
            ], 403);
        }

        return $next($request);
    }
}
