<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\AccessTokenController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FavoritesListController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\NavbarController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShoppingCartItemController;
use App\Http\Controllers\StripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\Category;
use Stripe\Stripe;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// authentication Routes 

Route::post("/register",[AuthenticationController::class, 'register']);
Route::post('/login', [AuthenticationController::class , 'login']);
Route::post('/logout',[AuthenticationController::class, 'logout'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
// create a middleware that validates access token by retrieving it from http-only cookie instead of Authorization header 
Route::post("/access_tokens", [AuthenticationController::class, 'refreshAccessToken'])->middleware(['add_refresh_token_header','auth:sanctum','ability:'. TokenAbility::ISSUE_ACCESS_TOKEN->value]);

// user Routes
Route::get("/users", [UserController::class,'listUsers'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);

Route::get("/users/{id}", [UserController::class,'retrieveUsers'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
// Route::patch("/users/{id}", [UserController::class,'updateUser'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);

Route::patch("/users/user", [UserController::class,'updateUser'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/users/admins", [UserController::class,'createAdmin'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/users/clients", [UserController::class,'createClient'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);


// Products Controller Routes 
Route::get("/products", [ProductController::class, "listProducts"]);
Route::get("/products/popular", [ProductController::class, "listPopularProducts"]);
Route::get("/products/{id}", [ProductController::class, "retrieveProduct"]);
Route::get("/products/{id}/colors", [ProductController::class, "productColor"]);
Route::get("/products/{id}/sizes", [ProductController::class, "productSize"]);
Route::post("/products", [ProductController::class , "createProduct"]);
Route::delete("/products/{id}", [ProductController::class, "deleteProduct"]);

// Filter Controller Routes

Route::get("/filters",[FilterController::class, "show"]);

// Navbar Controller Routes

Route::post("/categories", [CategoryController::class, "createCategory"]);
Route::get("/categories/flat", [CategoryController::class, "listCategoriesFlat"]);
Route::get("/categories/nested", [CategoryController::class, "listCategoriesNested"]);
Route::get("/categories/nested-depth-1",[CategoryController::class, "listCategoriesNested"]);
Route::get("/categories/sales", [CategoryController::class, "listSalesCategories"]);
Route::get("/categories/new-arrivals", [CategoryController::class, "listNewArrivalsCategories"]);
Route::patch("/categories/{id}", [CategoryController::class, "patchCategory"]);
Route::get("/categories/{id}", [CategoryController::class, "getCategory"]);

// Stripe Controller Routes 

Route::post('/checkout/products',[StripeController::class, "checkoutProducts"])->middleware(["auth:sanctum",'ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/webhook", [StripeController::class,"stripeWebhookEventListener"]);
Route::post("users/user/orders/canceled", [StripeController::class,"cancelOrder"])->middleware('auth:sanctum','ability:'. TokenAbility::ACCESS_API->value);
// Route::post('/checkout/shopping_cart')

// Reviews Controller Routes 

Route::get("/products/{product_id}/reviews" , [ReviewController::class, "listReviewsByProduct"]);
Route::get("/products/{product_id}/users/user/is_reviewed", [ReviewController::class, "checkIfUserReviewed"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/reviews/{id}/like", [ReviewController::class , "likeReview"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/reviews", [ReviewController::class , "createReview"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::delete("/reviews/{id}", [ReviewController::class,"deleteReview"])->middleware(["auth:sanctum",'ability:'. TokenAbility::ACCESS_API->value]);
// Route::get('/users/user/products/{product_id}/reviews') for fetching a review of a user of a product 

// Order Controller Routes

Route::get("users/user/orders", [OrderController::class, "listOrders"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::get("users/user/orders/canceled", [OrderController::class,'listCanceledOrders'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
//Route::get("/orders", [OrderController::class,"listOrders"])->middleware(["auth:sanctum","ability:".TokenAbility::Access_Api->value]);


// Favorite Controller Routes

Route::post("/favorites", [FavoriteController::class, "createFavorite"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::get("/users/user/favorites", [FavoriteController::class, "listByUser"])->middleware(["auth:sanctum",'ability:'. TokenAbility::ACCESS_API->value]);
Route::get("/favorites_lists/{id}/favorites", [FavoriteController::class, "listByFavoritesList"]);

// FavoritesList Controller Routes

Route::get("/favorites_lists",[FavoritesListController::class, "listFavoritesList"]);
Route::post("/favorites_lists/{id}/like",[FavoritesListController::class, "likeFavoritesList"])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);
Route::post("/favorites_lists/{id}/view",[FavoritesListController::class, "viewFavoritesList"]);
Route::get('/users/user/favorites_lists',[FavoritesListController::class, "retrieveByUser"])->middleware(["auth:sanctum",'ability:'. TokenAbility::ACCESS_API->value]);
Route::get("/favorites_lists/{id}", [FavoritesListController::class, "retrieveById"]);
Route::patch("/favorites_lists/{id}",[FavoritesListController::class,'updateFavoritesList'])->middleware(['auth:sanctum','ability:'. TokenAbility::ACCESS_API->value]);

// ShoppingCartItems Controller Routes 

Route::post("/users/user/shopping_carts/items",[ShoppingCartItemController::class, 'createItemAuthenticated'])->middleware(['auth:sanctum','ability'.TokenAbility::ACCESS_API->value]);
Route::post("/users/anonymous/shopping_carts/items",[ShoppingCartItemController::class,'createItemAnonymous']);
// Route::patch('/users/user/shopping_carts/items')
// Route::patch('/users/anonymous/shopping_carts/items')



Route::post("/test" , [Controller::class, "test"]);





// admin :                      
// update favorites lists 
// delete reviews 
// create products
// delete products 

// super-admin :  
// register admins                     
// update favorites lists 
// delete reviews 
// create products
// delete products 

