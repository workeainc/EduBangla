<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['Super Admin', 'School Admin', 'Teacher', 'Student', 'Parent', 'Accountant', 'Librarian', 'Staff'] as $name) {
            Role::findOrCreate($name, 'web');
        }
    }
}
