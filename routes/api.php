<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ManualPresenceRequestController;
use App\Http\Controllers\PresenceConfigController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication required)
Route::group([
    'middleware' => ['api', 'rate.limit'],
    'prefix' => 'public'
], function () {
    Route::post('presence', [PresenceController::class, 'publicPresence']);
});

// Authentication routes with rate limiting
Route::group([
    'middleware' => ['api', 'rate.limit'],
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user-profile', [AuthController::class, 'userProfile']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Plan management routes (Super Admin only)
    Route::apiResource('plans', PlanController::class);
    Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus']);
    
    // Company management routes (Super Admin only)
    Route::apiResource('companies', CompanyController::class);
    Route::patch('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus']);
    
    // Division management routes (Super Admin & Admin Company)
    Route::apiResource('divisions', DivisionController::class);
    Route::patch('divisions/{division}/toggle-status', [DivisionController::class, 'toggleStatus']);
    
    // User management routes (Super Admin & Admin Company)
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'updateProfile']);
    
    // Presence system routes
    Route::prefix('presence')->group(function () {
        Route::get('/', [PresenceController::class, 'index']);
        Route::post('checkin', [PresenceController::class, 'checkin']);
        Route::post('checkout', [PresenceController::class, 'checkout']);
        Route::get('status', [PresenceController::class, 'status']);
        Route::get('today', [PresenceController::class, 'today']);
        Route::get('history', [PresenceController::class, 'history']);
        Route::get('company-history', [PresenceController::class, 'companyHistory']);
        Route::get('{presence}', [PresenceController::class, 'show']);
        Route::delete('{presence}', [PresenceController::class, 'destroy']);
    });
    
    // Manual presence request routes
    Route::prefix('manual-presence-requests')->group(function () {
        Route::get('/', [ManualPresenceRequestController::class, 'index']);
        Route::post('/', [ManualPresenceRequestController::class, 'store']);
        Route::get('my-requests', [ManualPresenceRequestController::class, 'myRequests']);
        Route::get('{request}', [ManualPresenceRequestController::class, 'show']);
        Route::put('{request}', [ManualPresenceRequestController::class, 'update']);
        Route::patch('{request}/approve', [ManualPresenceRequestController::class, 'approve']);
        Route::patch('{request}/reject', [ManualPresenceRequestController::class, 'reject']);
        Route::delete('{request}', [ManualPresenceRequestController::class, 'destroy']);
    });
    
    // Presence configuration routes (Super Admin & Admin Company)
    Route::prefix('presence-configs')->group(function () {
        Route::get('/', [PresenceConfigController::class, 'index']);
        Route::post('/', [PresenceConfigController::class, 'store']);
        Route::get('active', [PresenceConfigController::class, 'getActiveConfig']);
        Route::get('{config}', [PresenceConfigController::class, 'show']);
        Route::put('{config}', [PresenceConfigController::class, 'update']);
        Route::patch('{config}/toggle-status', [PresenceConfigController::class, 'toggleStatus']);
        Route::delete('{config}', [PresenceConfigController::class, 'destroy']);
    });
});