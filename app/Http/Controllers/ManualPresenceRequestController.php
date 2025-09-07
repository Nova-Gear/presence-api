<?php

namespace App\Http\Controllers;

use App\Models\ManualPresenceRequest;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ManualPresenceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin|admin_company')->only(['index', 'approve', 'reject']);
    }

    /**
     * Display a listing of manual presence requests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::with(['user', 'approver']);

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            $requests = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve manual presence requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created manual presence request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'request_type' => 'required|in:sick,leave,vacation,business_trip,other',
                'start_date' => 'required|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
                'reason' => 'required|string|max:500',
                'attachment_path' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if there's already a pending request for this date range
            $startDate = $request->start_date;
            $endDate = $request->end_date ?? $request->start_date;
            
            $existingRequest = ManualPresenceRequest::where('user_id', $user->id)
                                                  ->where('status', 'pending')
                                                  ->where(function($q) use ($startDate, $endDate) {
                                                      $q->whereDate('start_date', '<=', $endDate)
                                                        ->whereDate('end_date', '>=', $startDate);
                                                  })
                                                  ->first();
            


            if ($existingRequest) {
                return response()->json([
                    'error' => 'Manual presence request for this date already exists'
                ], 400);
            }

            $manualRequest = ManualPresenceRequest::create([
                'user_id' => $user->id,
                'request_type' => $request->request_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'attachment_path' => $request->attachment_path,
                'status' => ManualPresenceRequest::STATUS_PENDING
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manual presence request created successfully',
                'data' => $manualRequest->load(['user', 'approver'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create manual presence request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified manual presence request
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::with(['user', 'approver']);

            // Filter by user for employees or by company for admin_company
            if ($user->role === 'employee') {
                $query->where('user_id', $user->id);
            } elseif ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $manualRequest = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $manualRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Manual presence request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified manual presence request (only for pending requests)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $manualRequest = ManualPresenceRequest::where('user_id', $user->id)
                                                 ->where('id', $id)
                                                 ->pending()
                                                 ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'request_type' => 'required|in:single_day,date_range',
                'start_date' => 'required|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
                'reason' => 'required|string|max:500',
                'attachment_path' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $manualRequest->update([
                'request_type' => $request->request_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'attachment_path' => $request->attachment_path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manual presence request updated successfully',
                'data' => $manualRequest->fresh()->load(['user', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update manual presence request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a manual presence request
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::with('user')->pending();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $manualRequest = $query->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Manual presence request not found or already processed'
            ], 404);
        }

        try {

            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if there's already a presence record for this date
            $existingPresence = Presence::where('user_id', $manualRequest->user_id)
                                      ->whereDate('presence_time', $manualRequest->start_date)
                                      ->first();

            if ($existingPresence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presence record already exists for this date'
                ], 400);
            }

            // Approve the request
            $manualRequest->approve($user->id, $request->approval_notes);

            // Create the presence record for checkin
            $presence = Presence::create([
                'user_id' => $manualRequest->user_id,
                'type' => 'checkin',
                'presence_type' => 'manual',
                'presence_time' => Carbon::parse($manualRequest->start_date)->setTime(8, 0, 0), // Default checkin time
                'notes' => 'Manual entry approved by ' . $user->name . '. Reason: ' . $manualRequest->reason,
                'is_valid' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manual presence request approved successfully',
                'data' => [
                    'request' => $manualRequest->fresh()->load(['user', 'approver']),
                    'presence' => $presence->load('user')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve manual presence request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a manual presence request
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::with('user')->pending();

            // Filter by company for admin_company
            if ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $manualRequest = $query->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Reject the request
            $manualRequest->reject($user->id, $request->rejection_reason);

            return response()->json([
                'success' => true,
                'message' => 'Manual presence request rejected successfully',
                'data' => $manualRequest->fresh()->load(['user', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject manual presence request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user's manual presence requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::where('user_id', $user->id)
                                        ->with(['user', 'approver']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            $requests = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your manual presence requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified manual presence request (only pending requests)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ManualPresenceRequest::query();

            // Filter by user for employees or by company for admin_company
            if ($user->role === 'employee') {
                $query->where('user_id', $user->id)->pending();
            } elseif ($user->role === 'admin_company') {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            }

            $manualRequest = $query->findOrFail($id);

            // Only allow deletion of pending requests
            if ($user->role === 'employee' && !$manualRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be deleted'
                ], 400);
            }

            $manualRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Manual presence request deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete manual presence request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
