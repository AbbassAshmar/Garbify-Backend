<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\User;
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
        $dummyRole = Role::create(["name" => "dummy"]);

        // Create permissions
        $viewFavoritesListPermission = Permission::create(['name' => 'view_favorites_list']);
        $manageOwnShoppingCartPermission = Permission::create(['name' => 'manage_shopping_cart']);
        $manageOwnOrderPermission = Permission::create(['name' => 'manage_order']);

        $createProductPermission = Permission::create(['name' => 'create_product']);
        $updateProductPermission = Permission::create(['name' => 'update_product']);

        $updateFavoritesListPermission= Permission::create(['name' => 'update_favorites_list']);
        $deleteAnyReviewPermission= Permission::create(['name'=>'delete_any_review']);

        $registerAdminPermission = Permission::create(['name' => 'register_admin']);
        $retrieveAdminPermission = Permission::create(['name' => 'retrieve_admin']);
        $editAdminPermission = Permission::create(['name' => 'edit_admin']);
        $deleteAdminPermission = Permission::create(['name' => 'delete_admin']);

        $retrieveSuperAdminPermission = Permission::create(['name' => 'retrieve_super_admin']);
        $editSuperAdminPermission = Permission::create(['name' => 'edit_super_admin']);

        // Sync permissions to roles
        $superAdminRole->syncPermissions([
            $createProductPermission, 
            $updateProductPermission, 
            $updateFavoritesListPermission,
            $deleteAnyReviewPermission,

            $registerAdminPermission,
            $deleteAdminPermission,
            $editAdminPermission,
            $retrieveAdminPermission,

            $editSuperAdminPermission,
            $retrieveSuperAdminPermission,

            $viewFavoritesListPermission,
            $manageOwnShoppingCartPermission,
            $manageOwnOrderPermission,
        ]);

        $adminRole->syncPermissions([
            $createProductPermission, 
            $updateProductPermission,
            $updateFavoritesListPermission,
            $deleteAnyReviewPermission,

            $viewFavoritesListPermission,
            $manageOwnShoppingCartPermission,
            $manageOwnOrderPermission,
        ]);

        $clientRole->syncPermissions([
            $viewFavoritesListPermission,
            $manageOwnShoppingCartPermission,
            $manageOwnOrderPermission,
        ]);

        $dummyRole->syncPermissions([
            $viewFavoritesListPermission,
            $manageOwnShoppingCartPermission,
            $manageOwnOrderPermission,
        ]);
    }
}
