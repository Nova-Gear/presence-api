<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $plans = Plan::active()->get();
            
            return response()->json([
                'success' => true,
                'data' => $plans,
                'message' => 'Plans retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans',
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
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:plans',
                'description' => 'nullable|string',
                'employee_limit' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'billing_cycle' => 'required|in:monthly,yearly',
                'is_active' => 'boolean'
            ]);

            $plan = Plan::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Plan created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $plan = Plan::with('companies')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Plan retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $plan = Plan::findOrFail($id);
            
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:plans,name,' . $id,
                'description' => 'nullable|string',
                'employee_limit' => 'sometimes|required|integer|min:1',
                'price' => 'sometimes|required|numeric|min:0',
                'billing_cycle' => 'sometimes|required|in:monthly,yearly',
                'is_active' => 'boolean'
            ]);

            $plan->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $plan->fresh(),
                'message' => 'Plan updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $plan = Plan::findOrFail($id);
            
            // Check if plan has associated companies
            if ($plan->companies()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with associated companies'
                ], 400);
            }
            
            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plan deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle plan status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $plan = Plan::findOrFail($id);
            $plan->update(['is_active' => !$plan->is_active]);

            return response()->json([
                'success' => true,
                'data' => $plan->fresh(),
                'message' => 'Plan status updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
