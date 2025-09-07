<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }
        } catch (JWTException $e) {
            return $this->serverErrorResponse('Could not create token');
        }

        $user = auth()->user();
        
        // Check if user is active
        if (!$user->is_active) {
            return $this->unauthorizedResponse('Account is deactivated');
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'role' => 'required|string|in:super_admin,admin_company,employee',
            'company_id' => 'nullable|exists:companies,id',
            'division_id' => 'nullable|exists:divisions,id',
            'employee_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'plan_id' => 'nullable|exists:plans,id',
            'company_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if plan is active (if plan_id is provided)
        if ($request->plan_id) {
            $plan = \App\Models\Plan::find($request->plan_id);
            if (!$plan || !$plan->is_active) {
                return $this->errorResponse('Selected plan is not active');
            }
        }

        $userData = array_merge(
            $validator->validated(),
            [
                'password' => Hash::make($request->password),
                'is_active' => true
            ]
        );

        $company = null;
        // Create company if company_name is provided
        if ($request->company_name) {
            $company = \App\Models\Company::create([
                'name' => $request->company_name,
                'email' => $request->email, // Use user's email for company
                'plan_id' => $request->plan_id,
                'is_active' => true
            ]);
            $userData['company_id'] = $company->id;
        }

        $user = User::create($userData);

        // Generate JWT token for the new user
        $token = JWTAuth::fromUser($user);

        $data = [
            'user' => $user,
            'token' => $token
        ];

        if ($company) {
            $data['company'] = $company;
        }

        return $this->successResponse($data, 'User successfully registered', 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->successResponse(null, 'Successfully logged out');
        } catch (JWTException $e) {
            return $this->serverErrorResponse('Failed to logout, please try again');
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $data = [
                'token' => $token,
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ];
            return $this->successResponse($data, 'Token refreshed successfully');
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Token cannot be refreshed');
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        $user = auth()->user()->load(['company', 'division']);
        
        return $this->successResponse($user, 'User profile retrieved successfully');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth()->user()->load(['company', 'division']);
        
        $data = [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user
        ];
        
        return $this->successResponse($data, 'Login successful');
    }
}
