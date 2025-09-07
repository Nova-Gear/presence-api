<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin,admin_company')->except(['show', 'profile', 'updateProfile']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['company', 'division']);

            // Filter by company if provided
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by division if provided
            if ($request->has('division_id')) {
                $query->where('division_id', $request->division_id);
            }

            // Filter by role if provided
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            // Filter by status if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $users = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'role' => 'required|in:super_admin,admin_company,employee',
                'company_id' => 'required_unless:role,super_admin|exists:companies,id',
                'division_id' => 'required_if:role,employee|exists:divisions,id',
                'is_active' => 'boolean'
            ]);

            // Check if company exists and is active
            if (isset($validated['company_id'])) {
                $company = Company::findOrFail($validated['company_id']);
                if (!$company->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create user for inactive company'
                    ], 400);
                }

                // Check employee limit for the company
                if ($validated['role'] === 'employee' && $company->hasReachedEmployeeLimit()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company has reached its employee limit'
                    ], 400);
                }
            }

            // Check if division belongs to the company
            if (isset($validated['division_id']) && isset($validated['company_id'])) {
                $division = Division::findOrFail($validated['division_id']);
                if ($division->company_id !== (int)$validated['company_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Division does not belong to the specified company'
                    ], 400);
                }
            }

            // Hash password
            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);
            $user->load(['company', 'division']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['company', 'division']);
            
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'role' => 'sometimes|in:super_admin,admin_company,employee',
                'company_id' => 'sometimes|exists:companies,id',
                'division_id' => 'sometimes|exists:divisions,id',
                'is_active' => 'boolean'
            ]);

            // Check if company exists and is active when updating company
            if (isset($validated['company_id'])) {
                $company = Company::findOrFail($validated['company_id']);
                if (!$company->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot assign user to inactive company'
                    ], 400);
                }
            }

            // Check if division belongs to the company
            if (isset($validated['division_id'])) {
                $companyId = $validated['company_id'] ?? $user->company_id;
                if ($companyId) {
                    $division = Division::findOrFail($validated['division_id']);
                    if ($division->company_id !== (int)$companyId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Division does not belong to the specified company'
                        ], 400);
                    }
                }
            }

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            $user->load(['company', 'division']);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Prevent deletion of super admin
            if ($user->role === 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete super admin user'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            // Prevent deactivation of super admin
            if ($user->role === 'super_admin' && $user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate super admin user'
                ], 400);
            }

            $user->update(['is_active' => !$user->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => $user
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['company', 'division']);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update current user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'password' => 'sometimes|string|min:8|confirmed'
            ]);

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            $user->load(['company', 'division']);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
