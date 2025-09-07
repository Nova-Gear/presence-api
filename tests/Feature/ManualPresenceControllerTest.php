<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use App\Models\ManualPresenceRequest;
use App\Models\Plan;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ManualPresenceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $employee;
    protected $admin;
    protected $company;
    protected $division;
    protected $employeeToken;
    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        
        Storage::fake('local');
        
        $plan = Plan::factory()->create(['is_active' => true]);
        $this->company = Company::factory()->create(['plan_id' => $plan->id]);
        $this->division = Division::factory()->create(['company_id' => $this->company->id]);
        
        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'is_active' => true,
            'role' => 'employee'
        ]);
        
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'is_active' => true,
            'role' => 'admin_company'
        ]);
        
        $this->employeeToken = JWTAuth::fromUser($this->employee);
        $this->adminToken = JWTAuth::fromUser($this->admin);
    }

    public function test_employee_can_create_manual_presence_request(): void
    {
        $requestData = [
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'request_type' => 'other',
            'reason' => 'Forgot to checkin this morning',
            'attachment_path' => 'evidence/evidence.jpg'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employeeToken,
        ])->postJson('/api/manual-presence-requests', $requestData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'start_date',
                        'end_date',
                        'request_type',
                        'reason',
                        'status'
                    ]
                ]);

        $this->assertDatabaseHas('manual_presence_requests', [
            'user_id' => $this->employee->id,
            'reason' => 'Forgot to checkin this morning',
            'status' => 'pending'
        ]);


    }

    public function test_manual_presence_request_fails_with_invalid_data(): void
    {
        $requestData = [
            'start_date' => 'invalid-date',
            'end_date' => 'invalid-date',
            'request_type' => 'invalid-type',
            'reason' => ''
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employeeToken,
        ])->postJson('/api/manual-presence-requests', $requestData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_date', 'end_date', 'request_type', 'reason']);
    }

    public function test_employee_cannot_create_duplicate_request_for_same_date(): void
    {
        $date = Carbon::now()->format('Y-m-d');
        
        // Create existing request
        ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'pending'
        ]);

        $requestData = [
            'start_date' => $date,
            'end_date' => $date,
            'request_type' => 'other',
            'reason' => 'Another request for same date'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employeeToken,
        ])->postJson('/api/manual-presence-requests', $requestData);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Manual presence request for this date already exists'
                ]);
    }

    public function test_admin_can_approve_manual_presence_request(): void
    {
        $manualPresence = ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->patchJson("/api/manual-presence-requests/{$manualPresence->id}/approve", [
            'approval_notes' => 'Approved - valid reason provided'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'request' => [
                            'id',
                            'status',
                            'approved_by',
                            'approved_at',
                            'approval_notes'
                        ],
                        'presence' => [
                            'id',
                            'user_id',
                            'type',
                            'presence_time'
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('manual_presence_requests', [
            'id' => $manualPresence->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approval_notes' => 'Approved - valid reason provided'
        ]);

        // Check if presence record was created
        $this->assertDatabaseHas('presences', [
            'user_id' => $this->employee->id,
            'type' => 'checkin',
            'presence_type' => 'manual'
        ]);
    }

    public function test_admin_can_reject_manual_presence_request(): void
    {
        $manualPresence = ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->patchJson("/api/manual-presence-requests/{$manualPresence->id}/reject", [
            'rejection_reason' => 'Rejected - insufficient evidence'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'status',
                        'approved_by',
                        'approved_at',
                        'approval_notes'
                    ]
                ]);

        $this->assertDatabaseHas('manual_presence_requests', [
            'id' => $manualPresence->id,
            'status' => 'rejected',
            'approved_by' => $this->admin->id,
            'approval_notes' => 'Rejected - insufficient evidence'
        ]);

        // Ensure no presence record was created
        $this->assertDatabaseMissing('presences', [
            'user_id' => $this->employee->id,
            'date' => $manualPresence->start_date,
            'is_manual' => true
        ]);
    }

    public function test_employee_cannot_approve_requests(): void
    {
        $manualPresence = ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employeeToken,
        ])->patchJson("/api/manual-presence-requests/{$manualPresence->id}/approve");

        $response->assertStatus(403);
    }

    public function test_admin_can_get_pending_requests(): void
    {
        // Create requests from different employees
        $employee2 = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'role' => 'employee'
        ]);

        ManualPresenceRequest::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'status' => 'pending'
        ]);
        
        ManualPresenceRequest::factory()->create([
            'user_id' => $employee2->id,
            'status' => 'pending'
        ]);
        
        ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'approved'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/manual-presence-requests?status=pending');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'user_id',
                                'start_date',
                                'end_date',
                                'request_type',
                                'reason',
                                'status',
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

        // Should only return pending requests (3 total)
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_employee_can_get_own_requests(): void
    {
        // Create requests for this employee
        ManualPresenceRequest::factory()->count(2)->create([
            'user_id' => $this->employee->id
        ]);
        
        // Create request for another employee
        $employee2 = User::factory()->create([
            'company_id' => $this->company->id,
            'division_id' => $this->division->id,
            'role' => 'employee'
        ]);
        ManualPresenceRequest::factory()->create([
            'user_id' => $employee2->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employeeToken,
        ])->getJson('/api/manual-presence-requests/my-requests');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'start_date',
                                'end_date',
                                'request_type',
                                'reason',
                                'status',
                                'approval_notes',
                                'created_at'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);

        // Should only return this employee's requests (2 total)
        $this->assertCount(2, $response->json('data.data'));
        
        // Verify all returned requests belong to this employee
        foreach ($response->json('data.data') as $request) {
            $this->assertEquals($this->employee->id, ManualPresenceRequest::find($request['id'])->user_id);
        }
    }

    public function test_cannot_approve_already_processed_request(): void
    {
        $manualPresence = ManualPresenceRequest::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'approved'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->patchJson("/api/manual-presence-requests/{$manualPresence->id}/approve");

        $response->assertStatus(404);
    }

    public function test_unauthorized_access_fails(): void
    {
        $response = $this->postJson('/api/manual-presence-requests', [
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'request_type' => 'other',
            'reason' => 'Test reason'
        ]);

        $response->assertStatus(401);
    }
}
