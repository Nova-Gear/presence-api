<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Division;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test plan
        $plan = Plan::create([
            'name' => 'Basic Plan',
            'description' => 'Basic subscription plan for testing',
            'employee_limit' => 100,
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        // Create a test company
        $company = Company::create([
            'name' => 'Test Company',
            'address' => '123 Test Street',
            'phone' => '+1234567890',
            'email' => 'contact@testcompany.com',
            'plan_id' => $plan->id,
            'is_active' => true,
        ]);

        // Create a test division
        $division = Division::create([
            'company_id' => $company->id,
            'name' => 'IT Department',
            'description' => 'Information Technology Department',
            'is_active' => true,
        ]);

        // Create Super Admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'company_id' => null,
            'division_id' => null,
            'is_active' => true,
        ]);

        // Create Company Admin user
        User::create([
            'name' => 'Company Admin',
            'email' => 'companyadmin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin_company',
            'company_id' => $company->id,
            'division_id' => null,
            'is_active' => true,
        ]);

        // Create Employee user
        User::create([
            'name' => 'John Employee',
            'email' => 'employee@test.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'company_id' => $company->id,
            'division_id' => $division->id,
            'is_active' => true,
        ]);

        echo "Test users created successfully:\n";
        echo "- Super Admin: admin@test.com / password123\n";
        echo "- Company Admin: companyadmin@test.com / password123\n";
        echo "- Employee: employee@test.com / password123\n";
    }
}
