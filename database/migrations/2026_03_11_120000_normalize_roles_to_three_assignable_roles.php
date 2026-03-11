<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $subscriberRole = Role::firstOrCreate(['name' => 'subscriber', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $viewOnlyRole = Role::firstOrCreate(['name' => 'view_only', 'guard_name' => 'web']);

        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);
        $subscriberRole->syncPermissions($allPermissions);

        $employeeRole->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'appointments.create',
            'appointments.edit',
        ])->get());

        $viewOnlyRole->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'categories.view',
            'services.view',
            'users.view',
        ])->get());

        $moderatorRole = Role::where('name', 'moderator')->first();

        if ($moderatorRole) {
            foreach ($moderatorRole->users as $user) {
                $user->assignRole($subscriberRole);
                $user->removeRole($moderatorRole);
            }

            $moderatorRole->permissions()->detach();
            $moderatorRole->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);

        $moderatorRole->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',
        ])->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
