<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin')->except(['show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $companies = Company::with(['plan', 'divisions'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve companies',
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
                'name' => 'required|string|max:255|unique:companies',
                'email' => 'required|email|unique:companies',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'plan_id' => 'required|exists:plans,id',
                'is_active' => 'boolean'
            ]);

            // Check if plan exists and is active
            $plan = Plan::findOrFail($validated['plan_id']);
            if (!$plan->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is not active'
                ], 400);
            }

            $company = Company::create($validated);
            $company->load(['plan', 'divisions']);

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully',
                'data' => $company
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
                'message' => 'Failed to create company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        try {
            $company->load(['plan', 'divisions.users', 'users']);
            
            return response()->json([
                'success' => true,
                'data' => $company
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:companies,name,' . $company->id,
                'email' => 'sometimes|email|unique:companies,email,' . $company->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'plan_id' => 'sometimes|exists:plans,id',
                'is_active' => 'boolean'
            ]);

            // Check if plan exists and is active when updating plan
            if (isset($validated['plan_id'])) {
                $plan = Plan::findOrFail($validated['plan_id']);
                if (!$plan->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected plan is not active'
                    ], 400);
                }
            }

            $company->update($validated);
            $company->load(['plan', 'divisions']);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company
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
                'message' => 'Company not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        try {
            // Check if company has active users
            if ($company->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete company with existing users'
                ], 400);
            }

            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle company status
     */
    public function toggleStatus(Company $company): JsonResponse
    {
        try {
            $company->update(['is_active' => !$company->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Company status updated successfully',
                'data' => $company
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
