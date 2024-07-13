<?php

namespace App\Services\User;

use App\Helpers\ValidateResourceHelper;
use App\Models\User;
use App\Services\Product\Helpers\Filters\ColorFilter;
use App\Services\Product\Helpers\Filters\SizeFilter;
use App\Services\Product\Helpers\Filters\PriceFilter;
use App\Services\Product\Helpers\Filters\SaleFilter;
use App\Services\Product\Helpers\Filters\NewArrivalFilter;
use App\Services\Product\Helpers\Filters\CategoryFilter;
use Exception;
use Illuminate\Support\Facades\Storage;

class UserService {
    public function getByID($id){
        $user = User::find($id);
        ValidateResourceHelper::ensureResourceExists($user, 'User');
        return $user;
    }

    public function createUser($data, $role="client"){
        try {
            $profile_picture = null;
            if ($data['profile_picture']){
                $path = Storage::putFile('public/usersProfilePictures', $data['profile_picture']);
                $profile_picture = Storage::url($path); 
            }

            $new_user = User::create([
                'email' => $data['email'] , 
                'name'=>$data['name'],
                'password'=>$data['password'],
                'profile_picture'=>$profile_picture
            ]);

            $new_user->assignRole($role);

            return ["user" => $new_user, "error" => null];

        }catch(Exception $e){
            return  ["user" => null, "error" => $e];;
        }
    }


}

