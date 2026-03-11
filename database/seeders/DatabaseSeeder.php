<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Service;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if the settings table exists and is empty before seeding
        if (Schema::hasTable('settings') && Setting::count() === 0) {
            Setting::factory()->create();
        }

        // Check if the users table exists and is empty before creating user, permissions, and roles
        if (Schema::hasTable('users') && User::count() === 0) {
            $user = $this->createInitialUserWithPermissions();
            $this->createCategoriesAndServices($user);
        }
    }

    protected function createInitialUserWithPermissions()
    {
        // Define permissions list
        $permissions = [
            // Permission Management
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Appointment Management
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            // Category Management
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Service Management
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',

            // Settings
            'settings.edit'
        ];

        // Create each permission if it doesn't exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Create roles if they do not exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $subscriberRole = Role::firstOrCreate(['name' => 'subscriber']);
        $viewOnlyRole = Role::firstOrCreate(['name' => 'view_only']);

        // Admin and subscriber/admin both have full system access.
        $adminRole->syncPermissions(Permission::all());
        $subscriberRole->syncPermissions(Permission::all());
        $employeeRole->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'appointments.create',
            'appointments.edit',
        ])->get());

        // Create the initial admin user
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'status' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
        ]);

        // View-Only: read-only access
        $viewOnlyRole->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'categories.view',
            'services.view',
            'users.view',
        ])->get());

        // Assign the 'admin' role to the user
        $user->assignRole($adminRole);



         // Create admin as employee with additional details
        $employee = Employee::create([
            'user_id' => $user->id,
            'days' => [
                "monday" => ["06:00-22:00"],
                "tuesday" => ["06:00-15:00", "16:00-22:00"],
                "wednesday" => ["09:00-12:00", "14:00-23:00"],
                "thursday" => ["09:00-20:00"],
                "friday" => ["06:00-17:00"],
                "saturday" => ["05:00-18:00"]
            ],
            'slot_duration' => 30
        ]);

        return $user;
    }

    protected function createCategoriesAndServices(User $user)
    {
        // MEC branches (Mobile Examination Centers) – replace old categories
        $branches = [
            [
                'title' => 'MEC1',
                'slug' => 'mec1',
                'body' => 'Mobile Examination Center 1 – National Health and Nutrition Survey Program.',
                'city' => 'Jeddah',
                'address' => '',
                'map_link' => '',
            ],
            [
                'title' => 'MEC2',
                'slug' => 'mec2',
                'body' => 'Mobile Examination Center 2 – National Health and Nutrition Survey Program.',
                'city' => 'Jeddah',
                'address' => '',
                'map_link' => '',
            ],
            [
                'title' => 'MEC3',
                'slug' => 'mec3',
                'body' => 'Mobile Examination Center 3 – National Health and Nutrition Survey Program.',
                'city' => 'Jeddah',
                'address' => '',
                'map_link' => '',
            ],
        ];

        foreach ($branches as $branchData) {
            $branch = Category::create(array_merge($branchData, ['status' => 1, 'featured' => 0]));

            // One MEC examination service per branch (~1 hour, five tests)
            Service::create([
                'title' => 'MEC Health Examination',
                'slug' => $branch->slug . '-health-examination',
                'excerpt' => 'Approximately 1 hour – five tests conducted sequentially. National Health and Nutrition Survey.',
                'category_id' => $branch->id,
                'status' => 1,
            ]);
        }

        // Attach all services to the admin employee
        if ($user->employee) {
            $allServices = Service::all();
            $user->employee->services()->sync($allServices->pluck('id'));
        }
    }
}
