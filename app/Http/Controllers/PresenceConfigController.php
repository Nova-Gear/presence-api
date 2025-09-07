<?php

namespace App\Http\Controllers;

use App\Models\PresenceConfig;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PresenceConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin,admin_company');
    }

    /**
     * Display a listing of presence configurations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = PresenceConfig::with('company');

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->where('company_id', $user->company_id);
            }

            // Apply filters
            if ($request->has('company_id') && $user->role === 'super_admin') {
                $query->where('company_id', $request->company_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $configs = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $configs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve presence configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created presence configuration
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'checkin_start' => 'required|date_format:H:i',
                'checkin_end' => 'required|date_format:H:i|after:checkin_start',
                'checkout_start' => 'required|date_format:H:i|after:checkin_end',
                'checkout_end' => 'required|date_format:H:i|after:checkout_start',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check company access for admin_company
            if ($user->role === 'admin_company' && $request->company_id != $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to create config for this company'
                ], 403);
            }

            // Check if company exists and is active
            $company = Company::where('id', $request->company_id)
                             ->where('is_active', true)
                             ->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found or inactive'
                ], 404);
            }

            // Check if there's already an active config for this company
            if ($request->boolean('is_active', true)) {
                $existingActiveConfig = PresenceConfig::where('company_id', $request->company_id)
                                                    ->where('is_active', true)
                                                    ->first();

                if ($existingActiveConfig) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An active presence configuration already exists for this company'
                    ], 400);
                }
            }

            // Create datetime objects for validation
            $today = Carbon::today();
            $checkinStart = $today->copy()->setTimeFromTimeString($request->checkin_start);
            $checkinEnd = $today->copy()->setTimeFromTimeString($request->checkin_end);
            $checkoutStart = $today->copy()->setTimeFromTimeString($request->checkout_start);
            $checkoutEnd = $today->copy()->setTimeFromTimeString($request->checkout_end);

            $config = PresenceConfig::create([
                'company_id' => $request->company_id,
                'checkin_start' => $checkinStart,
                'checkin_end' => $checkinEnd,
                'checkout_start' => $checkoutStart,
                'checkout_end' => $checkoutEnd,
                'is_active' => $request->boolean('is_active', true)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Presence configuration created successfully',
                'data' => $config->load('company')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create presence configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified presence configuration
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = PresenceConfig::with('company');

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->where('company_id', $user->company_id);
            }

            $config = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence configuration not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified presence configuration
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = PresenceConfig::query();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->where('company_id', $user->company_id);
            }

            $config = $query->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'checkin_start' => 'required|date_format:H:i',
                'checkin_end' => 'required|date_format:H:i|after:checkin_start',
                'checkout_start' => 'required|date_format:H:i|after:checkin_end',
                'checkout_end' => 'required|date_format:H:i|after:checkout_start',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if trying to activate and there's already an active config
            if ($request->boolean('is_active') && !$config->is_active) {
                $existingActiveConfig = PresenceConfig::where('company_id', $config->company_id)
                                                    ->where('id', '!=', $config->id)
                                                    ->where('is_active', true)
                                                    ->first();

                if ($existingActiveConfig) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Another active presence configuration exists for this company. Deactivate it first.'
                    ], 400);
                }
            }

            // Create datetime objects for validation
            $today = Carbon::today();
            $checkinStart = $today->copy()->setTimeFromTimeString($request->checkin_start);
            $checkinEnd = $today->copy()->setTimeFromTimeString($request->checkin_end);
            $checkoutStart = $today->copy()->setTimeFromTimeString($request->checkout_start);
            $checkoutEnd = $today->copy()->setTimeFromTimeString($request->checkout_end);

            $config->update([
                'checkin_start' => $checkinStart,
                'checkin_end' => $checkinEnd,
                'checkout_start' => $checkoutStart,
                'checkout_end' => $checkoutEnd,
                'is_active' => $request->boolean('is_active', $config->is_active)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Presence configuration updated successfully',
                'data' => $config->fresh()->load('company')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update presence configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of presence configuration
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = PresenceConfig::query();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->where('company_id', $user->company_id);
            }

            $config = $query->findOrFail($id);

            // If trying to activate, check if there's already an active config
            if (!$config->is_active) {
                $existingActiveConfig = PresenceConfig::where('company_id', $config->company_id)
                                                    ->where('id', '!=', $config->id)
                                                    ->where('is_active', true)
                                                    ->first();

                if ($existingActiveConfig) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Another active presence configuration exists for this company. Deactivate it first.'
                    ], 400);
                }
            }

            $config->update([
                'is_active' => !$config->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Presence configuration status updated successfully',
                'data' => $config->fresh()->load('company')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle presence configuration status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active presence configuration for a company
     */
    public function getActiveConfig(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyId = $request->get('company_id', $user->company_id);

            // Check company access for admin_company
            if ($user->role === 'admin_company' && $companyId != $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this company config'
                ], 403);
            }

            $config = PresenceConfig::where('company_id', $companyId)
                                  ->active()
                                  ->with('company')
                                  ->first();

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active presence configuration found for this company'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active presence configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified presence configuration
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = PresenceConfig::query();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->where('company_id', $user->company_id);
            }

            $config = $query->findOrFail($id);

            // Prevent deletion of active configuration
            if ($config->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete active presence configuration. Deactivate it first.'
                ], 400);
            }

            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Presence configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete presence configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
