<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionRoleTableSeeder extends Seeder
{
    public function run()
    {
        $admin_permissions = Permission::all();
        Role::findOrFail(1)->permissions()->sync($admin_permissions->pluck('id'));

        $user_permissions = Permission::whereNotIn('title', [
            'user_management_access',
            'customer_create',
            'customer_edit',
            'customer_delete',
            'view_user_in_customers'
        ]);

        Role::findOrFail(2)->permissions()->sync($user_permissions->pluck('id'));
    }
}
