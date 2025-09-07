<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_be_created(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('employee', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_user_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);

        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertEquals($company->id, $user->company->id);
    }

    public function test_user_belongs_to_division(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);

        $this->assertInstanceOf(Division::class, $user->division);
        $this->assertEquals($division->id, $user->division->id);
    }

    public function test_user_has_many_presences(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->presences);
    }

    public function test_user_password_is_hashed(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'plainpassword',
            'role' => 'employee',
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    public function test_user_role_validation(): void
    {
        $validRoles = ['super_admin', 'admin_company', 'employee'];
        
        foreach ($validRoles as $role) {
            $company = Company::factory()->create();
            $division = Division::factory()->create(['company_id' => $company->id]);
            
            $user = User::factory()->create([
                'role' => $role,
                'company_id' => $company->id,
                'division_id' => $division->id
            ]);
            
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_can_be_activated_and_deactivated(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true
        ]);

        $this->assertTrue($user->is_active);

        $user->update(['is_active' => false]);
        $this->assertFalse($user->is_active);
    }

    public function test_user_email_must_be_unique(): void
    {
        $company = Company::factory()->create();
        $division = Division::factory()->create(['company_id' => $company->id]);
        
        User::factory()->create([
            'email' => 'unique@example.com',
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create([
            'email' => 'unique@example.com',
            'company_id' => $company->id,
            'division_id' => $division->id
        ]);
    }
}
