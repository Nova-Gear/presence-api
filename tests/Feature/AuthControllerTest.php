<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_register_successfully(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_company',
            'company_name' => 'Test Company',
            'plan_id' => $plan->id
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'company_id'
                        ],
                        'company' => [
                            'id',
                            'name',
                            'plan_id'
                        ],
                        'token'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'role' => 'admin_company'
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'plan_id' => $plan->id
        ]);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_inactive_plan(): void
    {
        $plan = Plan::factory()->create(['is_active' => false]);
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin_company',
            'company_name' => 'Test Company',
            'plan_id' => $plan->id
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Selected plan is not active'
                ]);
    }

    public function test_user_can_login_successfully(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role'
                        ],
                        'token',
                        'expires_in'
                    ]
                ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
    }

    public function test_login_fails_with_inactive_user(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => false
        ]);

        $loginData = [
            'email' => 'inactive@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ]);
    }

    public function test_user_can_refresh_token(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'token',
                        'expires_in'
                    ]
                ]);
    }

    public function test_user_can_logout(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
    }

    public function test_logout_fails_without_token(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        
        User::factory()->create([
            'email' => 'existing@example.com',
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'plan_id' => $plan->id
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }
}
