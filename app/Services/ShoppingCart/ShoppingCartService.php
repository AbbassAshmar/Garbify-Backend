<?php

namespace App\Services\User;

use App\Helpers\ValidateResourceHelper;
use App\Models\ShoppingCart;
use App\Models\User;
use App\Services\Product\Helpers\Filters\ColorFilter;
use App\Services\Product\Helpers\Filters\SizeFilter;
use App\Services\Product\Helpers\Filters\PriceFilter;
use App\Services\Product\Helpers\Filters\SaleFilter;
use App\Services\Product\Helpers\Filters\NewArrivalFilter;
use App\Services\Product\Helpers\Filters\CategoryFilter;
use Exception;
use Illuminate\Support\Facades\Storage;

class ShoppingCartService {

    public function createShoppingCartForUser($user){
        if (!$user) return ["shoppingCart" => null, 'error' => "No user provided"];

        $shopping_cart = ShoppingCart::where("user_id", $user->id)->first();
        if(!$shopping_cart){
            $shopping_cart = ShoppingCart::create([
                'user_id' => $user->id,
            ]);
        }

        return $shopping_cart;
    }


}

