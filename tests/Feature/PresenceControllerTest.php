<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use App\Models\Presence;
use App\Models\Plan;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class PresenceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $company;
    protected $division;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        $plan = Plan::factory()->create(['is_active' => true]);
        $this->company = Company::factory()->create(['plan_id' => $plan->id]);
        $this->division = Division::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'is_active' => true,
            'role' => 'employee'
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_user_can_checkin_successfully(): void
    {
        $checkinData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/presence/checkin', $checkinData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'presence_time',
                        'latitude',
                        'longitude',
                        'address'
                    ]
                ]);

        $this->assertDatabaseHas('presences', [
            'user_id' => $this->user->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ]);
    }

    public function test_checkin_fails_when_already_checked_in(): void
    {
        // Create existing checkin presence for today
        Presence::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'checkin',
            'presence_type' => 'manual',
            'presence_time' => Carbon::now(),
            'is_valid' => true
        ]);

        $checkinData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/presence/checkin', $checkinData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'You have already checked in today'
                ]);
    }

    public function test_user_can_checkout_successfully(): void
    {
        // Create existing checkin
        $presence = Presence::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'checkin',
            'presence_type' => 'manual',
            'presence_time' => Carbon::now()->subHours(2),
            'is_valid' => true
        ]);

        $checkoutData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/presence/checkout', $checkoutData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'presence_time',
                        'latitude',
                        'longitude',
                        'address',
                        'work_duration'
                    ]
                ]);

        $this->assertDatabaseHas('presences', [
            'user_id' => $this->user->id,
            'type' => 'checkout',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ]);
    }

    public function test_checkout_fails_when_not_checked_in(): void
    {
        $checkoutData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/presence/checkout', $checkoutData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'No check-in record found for today'
                ]);
    }

    public function test_user_can_get_presence_history(): void
    {
        // Create some presence records
        Presence::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'checkin',
            'presence_type' => 'manual',
            'presence_time' => Carbon::now()->subDays(1),
            'is_valid' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/presence/history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'presence_time',
                                'type',
                                'presence_type',
                                'address',
                                'work_duration'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);
    }

    public function test_admin_can_get_company_presence_history(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'role' => 'admin_company',
            'is_active' => true
        ]);
        $adminToken = JWTAuth::fromUser($admin);

        // Create presence records for different users
        $employee1 = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'role' => 'employee'
        ]);
        $employee2 = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'role' => 'employee'
        ]);

        Presence::factory()->create(['user_id' => $employee1->id]);
        Presence::factory()->create(['user_id' => $employee2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/presence/company-history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'user_id',
                                'presence_time',
                                'type',
                                'presence_type',
                                'user' => [
                                    'id',
                                    'name',
                                    'email'
                                ]
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);
    }

    public function test_employee_cannot_access_company_presence_history(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/presence/company-history');

        $response->assertStatus(403);
    }

    public function test_checkin_fails_with_invalid_coordinates(): void
    {
        $checkinData = [
            'latitude' => 'invalid',
            'longitude' => 'invalid',
            'address' => ''
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/presence/checkin', $checkinData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['latitude', 'longitude', 'address']);
    }

    public function test_user_can_get_today_presence_status(): void
    {
        // Create today's presence
        $presence = Presence::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'checkin',
            'presence_type' => 'manual',
            'presence_time' => Carbon::today()->addHours(8),
            'is_valid' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/presence/today');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'presence' => [
                            'id',
                            'presence_time',
                            'type',
                            'presence_type',
                            'address',
                            'work_duration'
                        ],
                        'status'
                    ]
                ]);
    }

    public function test_today_presence_returns_null_when_no_checkin(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/presence/today');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Today presence retrieved successfully',
                    'data' => [
                        'presence' => null,
                        'status' => 'not_checked_in'
                    ]
                ]);
    }

    public function test_unauthorized_access_fails(): void
    {
        $response = $this->postJson('/api/presence/checkin', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'address' => 'Jakarta, Indonesia'
        ]);

        $response->assertStatus(401);
    }
}
