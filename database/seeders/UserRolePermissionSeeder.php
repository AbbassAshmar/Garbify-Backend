<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {  
        // create rules 
        $anonymousRole = Role::create(['name'=>'anonymous']);
        $clientRole = Role::create(['name'=>'client']);
        $adminRole = Role::create(['name' => 'admin']);
        $superAdminRole = Role::create(['name' => 'super admin']);

        // Create permissions
        $createProductPermission = Permission::create(['name' => 'create_product']);
        $updateProductPermission = Permission::create(['name' => 'update_product']);
        $updateFavoritesListPermission= Permission::create(['name' => 'update_favorites_list']);
        $registerAdminPermission = Permission::create(['name' => 'register_admin']);
        $deleteAnyReviewPermission= Permission::create(['name'=>'delete_any_review']);

        // Sync permissions to roles
        $superAdminRole->syncPermissions([
            $createProductPermission, 
            $updateProductPermission, 
            $registerAdminPermission,
            $updateFavoritesListPermission,
            $deleteAnyReviewPermission
        ]);
        $adminRole->syncPermissions([
            $createProductPermission, 
            $updateProductPermission,
            $updateFavoritesListPermission,
            $deleteAnyReviewPermission
        ]);
    }
}
