<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\PresenceConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Traits\ApiResponseTrait;

class PresenceController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin,admin_company')->only(['index', 'show', 'destroy']);
    }

    /**
     * Display a listing of presences with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Presence::with('user');

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            // Apply filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('date')) {
                $query->whereDate('presence_time', $request->date);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('presence_type')) {
                $query->where('presence_type', $request->presence_type);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            $presences = $query->orderBy('presence_time', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $presences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve presences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check in user
     */
    public function checkin(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user already checked in today
            $existingPresence = Presence::where('user_id', $user->id)
                                      ->today()
                                      ->first();

            if ($existingPresence) {
                return $this->errorResponse('You have already checked in today');
            }

            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'required|string|max:255',
                'checkin_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $checkinTime = Carbon::now();
            $status = Presence::PRESENT;

            // Check if late based on company config
            $config = PresenceConfig::where('company_id', $user->company_id)
                                   ->active()
                                   ->first();

            if ($config && $config->isLateCheckin($checkinTime)) {
                $status = Presence::LATE;
            }

            $data = [
                'user_id' => $user->id,
                'type' => 'checkin',
                'presence_type' => $request->get('presence_type', 'manual'),
                'presence_time' => $checkinTime,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'notes' => $request->notes,
                'is_valid' => true
            ];

            // Handle data upload (photo, RFID, etc.)
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = 'checkin_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('presence/photos', $filename, 'public');
                $data['data'] = $path;
            } elseif ($request->has('data')) {
                $data['data'] = $request->data;
            }

            $presence = Presence::create($data);

            return $this->successResponse($presence->load('user'), 'Check-in successful', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Check-in failed: ' . $e->getMessage());
        }
    }

    /**
     * Check out user
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user has checked in today
            $checkinRecord = Presence::getTodayCheckin($user->id);
            if (!$checkinRecord) {
                return $this->errorResponse('No check-in record found for today');
            }

            // Check if user already checked out today
            $checkoutRecord = Presence::getTodayCheckout($user->id);
            if ($checkoutRecord) {
                return $this->errorResponse('You have already checked out today');
            }

            $validator = Validator::make($request->all(), [
                'address' => 'required|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $checkoutTime = Carbon::now();
            $presenceType = 'manual';

            // Check if early checkout based on company config
            $config = PresenceConfig::where('company_id', $user->company_id)
                                   ->active()
                                   ->first();

            if ($config && $config->isEarlyCheckout($checkoutTime)) {
                $presenceType = 'early_leave';
            }

            $data = [
                'user_id' => $user->id,
                'type' => 'checkout',
                'presence_type' => $request->get('presence_type', $presenceType),
                'presence_time' => $checkoutTime,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'notes' => $request->notes,
                'is_valid' => true
            ];

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = 'checkout_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('presence/checkout', $filename, 'public');
                $data['data'] = json_encode(['photo_path' => $path]);
            }

            $checkout = Presence::create($data);

            return $this->successResponse($checkout->load('user'), 'Check-out successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Check-out failed: ' . $e->getMessage());
        }
    }

    /**
     * Get current user's presence status
     */
    public function status(): JsonResponse
    {
        try {
            $user = Auth::user();
            $checkinRecord = Presence::getTodayCheckin($user->id);
            $checkoutRecord = Presence::getTodayCheckout($user->id);
            $isCheckedIn = Presence::isUserCheckedIn($user->id);

            $config = PresenceConfig::where('company_id', $user->company_id)
                                   ->active()
                                   ->first();

            $data = [
                'checkin' => $checkinRecord,
                'checkout' => $checkoutRecord,
                'config' => $config,
                'is_checked_in' => $isCheckedIn,
                'is_completed' => $checkinRecord && $checkoutRecord,
                'can_checkin' => !$checkinRecord && $config ? $config->isValidCheckinWindow() : false,
                'can_checkout' => $isCheckedIn && $config ? $config->isValidCheckoutWindow() : false
            ];
            
            return $this->successResponse($data, 'Presence status retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get presence status: ' . $e->getMessage());
        }
    }

    /**
     * Get today's presence for current user
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $checkinRecord = Presence::getTodayCheckin($user->id);
            $isCheckedIn = Presence::isUserCheckedIn($user->id);
            
            $status = 'not_checked_in';
            if ($checkinRecord) {
                $checkoutRecord = Presence::getTodayCheckout($user->id);
                $status = $checkoutRecord ? 'checked_out' : 'checked_in';
            }
            
            $data = [
                'presence' => $checkinRecord,
                'status' => $status
            ];
            
            return $this->successResponse($data, 'Today presence retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get today presence: ' . $e->getMessage());
        }
    }

    /**
     * Public presence endpoint for external integrations
     * Accepts requests with or without authentication
     */
    public function publicPresence(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|integer|in:1,2,3', // 1=RFID, 2=Face Recognition, 3=Fingerprint
                'data' => 'required|string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address' => 'nullable|string|max:255',
                'token' => 'nullable|string' // Optional JWT token
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = null;
            $presenceTime = Carbon::now();

            // Try to authenticate with token if provided
            if ($request->has('token')) {
                try {
                    $token = $request->token;
                    $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                } catch (\Exception $e) {
                    // Token invalid, continue as device-only request
                }
            }

            // If no user found, try to find user by device data
            if (!$user) {
                // For RFID, try to find user by tag_id in user profile or device mapping
                if ($request->type == 1) {
                    // This would require a device_mappings table or user profile field
                    // For now, return error as we need user association
                    return $this->errorResponse('Device not registered to any user');
                }
                
                // For Face Recognition and Fingerprint, similar logic would apply
                return $this->errorResponse('Device authentication not implemented for this type');
            }

            // Check if user already has presence today
            $existingPresence = Presence::where('user_id', $user->id)
                ->whereDate('presence_time', Carbon::today())
                ->first();

            $type = $existingPresence ? 'checkout' : 'checkin';

            // Create presence record
            $presenceData = [
                'user_id' => $user->id,
                'type' => $type,
                'presence_type' => $this->mapDeviceType($request->type),
                'presence_time' => $presenceTime,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'data' => $request->data,
                'is_valid' => true
            ];

            $presence = Presence::create($presenceData);

            return $this->successResponse([
                'presence' => $presence->load('user'),
                'action' => $type
            ], ucfirst($type) . ' successful via external device', 201);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Public presence failed: ' . $e->getMessage());
        }
    }

    /**
     * Map device type to presence type
     */
    private function mapDeviceType(int $type): string
    {
        return match($type) {
            1 => 'rfid',
            2 => 'face_recognition',
            3 => 'fingerprint',
            default => 'device'
        };
    }

    /**
     * Get user's presence history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Presence::where('user_id', $user->id);

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            if ($request->has('presence_type')) {
                $query->byPresenceType($request->presence_type);
            }

            $presences = $query->orderBy('presence_time', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $presences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve presence history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Presence::with('user');

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $presence = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $presence
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Presence::query();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $presence = $query->findOrFail($id);

            // Delete associated photos
            if ($presence->checkin_photo && Storage::disk('public')->exists($presence->checkin_photo)) {
                Storage::disk('public')->delete($presence->checkin_photo);
            }
            if ($presence->checkout_photo && Storage::disk('public')->exists($presence->checkout_photo)) {
                Storage::disk('public')->delete($presence->checkout_photo);
            }

            $presence->delete();

            return response()->json([
                'success' => true,
                'message' => 'Presence deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete presence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get company presence history (Admin Company only)
     */
    public function companyHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Only admin_company can access this
            if ($user->role !== 'admin_company') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $query = Presence::with('user')
                           ->whereHas('user', function ($q) use ($user) {
                               $q->where('company_id', $user->company_id);
                           });

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            if ($request->has('presence_type')) {
                $query->byPresenceType($request->presence_type);
            }

            $presences = $query->orderBy('presence_time', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $presences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company presence history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
