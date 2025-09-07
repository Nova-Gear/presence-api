<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class DivisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin,admin_company')->except(['show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Division::with(['company', 'users']);

            // Filter by company if provided
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by status if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $divisions = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $divisions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve divisions',
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
                'description' => 'nullable|string|max:500',
                'company_id' => 'required|exists:companies,id',
                'is_active' => 'boolean'
            ]);

            // Check if company exists and is active
            $company = Company::findOrFail($validated['company_id']);
            if (!$company->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create division for inactive company'
                ], 400);
            }

            // Check for duplicate division name within the same company
            $existingDivision = Division::where('company_id', $validated['company_id'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingDivision) {
                return response()->json([
                    'success' => false,
                    'message' => 'Division name already exists in this company'
                ], 400);
            }

            $division = Division::create($validated);
            $division->load(['company', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Division created successfully',
                'data' => $division
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
                'message' => 'Failed to create division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Division $division): JsonResponse
    {
        try {
            $division->load(['company', 'users']);
            
            return response()->json([
                'success' => true,
                'data' => $division
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Division $division): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:500',
                'company_id' => 'sometimes|exists:companies,id',
                'is_active' => 'boolean'
            ]);

            // Check if company exists and is active when updating company
            if (isset($validated['company_id'])) {
                $company = Company::findOrFail($validated['company_id']);
                if (!$company->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot assign division to inactive company'
                    ], 400);
                }
            }

            // Check for duplicate division name within the same company
            if (isset($validated['name'])) {
                $companyId = $validated['company_id'] ?? $division->company_id;
                $existingDivision = Division::where('company_id', $companyId)
                    ->where('name', $validated['name'])
                    ->where('id', '!=', $division->id)
                    ->first();

                if ($existingDivision) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Division name already exists in this company'
                    ], 400);
                }
            }

            $division->update($validated);
            $division->load(['company', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Division updated successfully',
                'data' => $division
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
                'message' => 'Division not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Division $division): JsonResponse
    {
        try {
            // Check if division has active users
            if ($division->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete division with existing users'
                ], 400);
            }

            $division->delete();

            return response()->json([
                'success' => true,
                'message' => 'Division deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle division status
     */
    public function toggleStatus(Division $division): JsonResponse
    {
        try {
            $division->update(['is_active' => !$division->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Division status updated successfully',
                'data' => $division
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update division status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
