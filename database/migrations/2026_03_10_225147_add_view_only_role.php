<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $viewOnly = Role::firstOrCreate(['name' => 'view_only', 'guard_name' => 'web']);
        $viewOnly->syncPermissions(Permission::whereIn('name', [
            'appointments.view',
            'categories.view',
            'services.view',
            'users.view',
        ])->get());
    }

    public function down(): void
    {
        Role::where('name', 'view_only')->delete();
    }
};
