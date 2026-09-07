<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'View Dashboard', 'module' => 'Dashboard'],

            // Finance
            ['name' => 'finance.view', 'display_name' => 'View Finance Overview', 'module' => 'Finance'],
            ['name' => 'finance.create', 'display_name' => 'Create Finance Records', 'module' => 'Finance'],
            ['name' => 'finance.edit', 'display_name' => 'Edit Finance Records', 'module' => 'Finance'],
            ['name' => 'finance.delete', 'display_name' => 'Delete Finance Records', 'module' => 'Finance'],

            // Income
            ['name' => 'income.view', 'display_name' => 'View Income', 'module' => 'Income'],
            ['name' => 'income.create', 'display_name' => 'Create Income', 'module' => 'Income'],
            ['name' => 'income.edit', 'display_name' => 'Edit Income', 'module' => 'Income'],
            ['name' => 'income.delete', 'display_name' => 'Delete Income', 'module' => 'Income'],

            // Expenses
            ['name' => 'expenses.view', 'display_name' => 'View Expenses', 'module' => 'Expenses'],
            ['name' => 'expenses.create', 'display_name' => 'Create Expenses', 'module' => 'Expenses'],
            ['name' => 'expenses.edit', 'display_name' => 'Edit Expenses', 'module' => 'Expenses'],
            ['name' => 'expenses.delete', 'display_name' => 'Delete Expenses', 'module' => 'Expenses'],

            // Payments
            ['name' => 'payments.view', 'display_name' => 'View Payments', 'module' => 'Payments'],
            ['name' => 'payments.create', 'display_name' => 'Create Payments', 'module' => 'Payments'],
            ['name' => 'payments.edit', 'display_name' => 'Edit Payments', 'module' => 'Payments'],
            ['name' => 'payments.delete', 'display_name' => 'Delete Payments', 'module' => 'Payments'],
            ['name' => 'payments.reconcile', 'display_name' => 'Reconcile Payments', 'module' => 'Payments'],

            // Activities
            ['name' => 'activities.view', 'display_name' => 'View Activities', 'module' => 'Activities'],
            ['name' => 'activities.create', 'display_name' => 'Create Activities', 'module' => 'Activities'],
            ['name' => 'activities.edit', 'display_name' => 'Edit Activities', 'module' => 'Activities'],
            ['name' => 'activities.delete', 'display_name' => 'Delete Activities', 'module' => 'Activities'],

            // Food & Snacks
            ['name' => 'food.view', 'display_name' => 'View Food & Snacks', 'module' => 'Food'],
            ['name' => 'food.create', 'display_name' => 'Create Food Entries', 'module' => 'Food'],
            ['name' => 'food.edit', 'display_name' => 'Edit Food Entries', 'module' => 'Food'],
            ['name' => 'food.delete', 'display_name' => 'Delete Food Entries', 'module' => 'Food'],

            // Calendar
            ['name' => 'calendar.view', 'display_name' => 'View Calendar & Timeline', 'module' => 'Calendar'],

            // Scooter
            ['name' => 'scooter.view', 'display_name' => 'View Scooter Trips', 'module' => 'Scooter'],
            ['name' => 'scooter.create', 'display_name' => 'Start & Record Trips', 'module' => 'Scooter'],
            ['name' => 'scooter.edit', 'display_name' => 'Edit Scooter Trips', 'module' => 'Scooter'],
            ['name' => 'scooter.delete', 'display_name' => 'Delete Scooter Trips', 'module' => 'Scooter'],

            // Petrol
            ['name' => 'petrol.view', 'display_name' => 'View Petrol Entries', 'module' => 'Petrol'],
            ['name' => 'petrol.create', 'display_name' => 'Create Petrol Entries', 'module' => 'Petrol'],
            ['name' => 'petrol.edit', 'display_name' => 'Edit Petrol Entries', 'module' => 'Petrol'],
            ['name' => 'petrol.delete', 'display_name' => 'Delete Petrol Entries', 'module' => 'Petrol'],

            // Mistakes & Lessons
            ['name' => 'mistakes.view', 'display_name' => 'View Mistakes & Lessons', 'module' => 'Mistakes'],
            ['name' => 'mistakes.create', 'display_name' => 'Create Mistakes', 'module' => 'Mistakes'],
            ['name' => 'mistakes.edit', 'display_name' => 'Edit Mistakes', 'module' => 'Mistakes'],
            ['name' => 'mistakes.delete', 'display_name' => 'Delete Mistakes', 'module' => 'Mistakes'],

            // Analytics
            ['name' => 'analytics.view', 'display_name' => 'View Analytics & Reports', 'module' => 'Analytics'],

            // Settings & Administration
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'module' => 'Settings'],
            ['name' => 'users.manage', 'display_name' => 'Manage Users', 'module' => 'Administration'],
            ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'module' => 'Administration'],
            ['name' => 'permissions.manage', 'display_name' => 'Manage Permissions', 'module' => 'Administration'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['name']] = Permission::updateOrCreate(
                ['name' => $p['name']],
                [
                    'display_name' => $p['display_name'],
                    'module' => $p['module'],
                ]
            );
        }

        // Roles
        $adminRole = Role::updateOrCreate(
            ['name' => 'Admin'],
            ['display_name' => 'Administrator', 'description' => 'Complete full access to all system features']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'User'],
            ['display_name' => 'Standard User', 'description' => 'Full access to personal records and entries']
        );

        $viewerRole = Role::updateOrCreate(
            ['name' => 'Viewer'],
            ['display_name' => 'Read-Only Viewer', 'description' => 'Can only view records']
        );

        // Assign all permissions to Admin
        $adminRole->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissionModels)));

        // Assign standard permissions to User (everything except administration)
        $userPermissionIds = [];
        $viewerPermissionIds = [];

        foreach ($permissionModels as $name => $perm) {
            if (! str_contains($name, '.manage')) {
                $userPermissionIds[] = $perm->id;
            }
            if (str_ends_with($name, '.view')) {
                $viewerPermissionIds[] = $perm->id;
            }
        }

        $userRole->permissions()->sync($userPermissionIds);
        $viewerRole->permissions()->sync($viewerPermissionIds);
    }
}
