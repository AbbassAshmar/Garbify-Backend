<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FavoritesListController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\NavbarController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

Route::post("/register",[UserController::class, 'register']);
Route::post('/login', [UserController::class , 'login']);
Route::post("/register/admin" ,[UserController::class, "adminRegister"])->middleware(['auth:sanctum','permission:register_admin']);
Route::post('/logout',[UserController::class, 'logout'])->middleware(['auth:sanctum']);

// Products Controller Routes 

Route::get("/products", [ProductController::class, "listProducts"]);
Route::get("/products/popular",[ProductController::class, "listPopularProducts"]);
Route::get("/products/{id}", [ProductController::class, "retrieveProduct"]);
Route::get("/products/{id}/colors", [ProductController::class, "productColor"]);
Route::get("/products/{id}/sizes",[ProductController::class, "productSize"]);

// Filter Controller Routes

Route::get("/filters",[FilterController::class, "show"]);

// Navbar Controller Routes

Route::get("/categories", [NavbarController::class, "show"]);

// Stripe Controller Routes 

Route::post("/create-checkout-session",[StripeController::class, "stripeBase"])->middleware(["auth:sanctum"]);
Route::post("/webhook", [StripeController::class,"stripeWebhookEventListener"]);

// Reviews Controller Routes 

Route::get("/products/{product_id}/reviews" , [ReviewController::class, "listReviewsByProduct"]);
Route::get("/products/{product_id}/users/user/reviewed/", [ReviewController::class, "checkIfUserReviewed"])->middleware(['auth:sanctum']);
Route::post("/reviews/{id}/like", [ReviewController::class , "likeReview"])->middleware(['auth:sanctum']);
Route::post("/reviews", [ReviewController::class , "createReview"])->middleware(['auth:sanctum']);
Route::delete("/reviews/{id}", [ReviewController::class,"deleteReview"])->middleware(["auth:sanctum"]);

// Order Controller Routes

Route::get("/orders", [OrderController::class, "listOrders"])->middleware(['auth:sanctum']);

// Favorite Controller Routes

Route::post("/favorites", [FavoriteController::class, "createFavorite"])->middleware(['auth:sanctum']);
Route::get("/users/user/favorites", [FavoriteController::class, "listByUser"])->middleware(["auth:sanctum"]);
Route::get("/favorites_lists/{id}/favorites", [FavoriteController::class, "listByFavoritesList"]);

// FavoritesList Controller Routes

Route::get("/favorites_lists",[FavoritesListController::class, "listFavoritesList"]);
Route::post("/favorites_lists/{id}/like",[FavoritesListController::class, "likeFavoritesList"])->middleware(['auth:sanctum']);
Route::post("/favorites_lists/{id}/view",[FavoritesListController::class, "viewFavoritesList"]);
Route::get('/users/user/favorites_lists',[FavoritesListController::class, "retrieveByUser"])->middleware(["auth:sanctum"]);
Route::get("/favorites_lists/{id}", [FavoritesListController::class, "retrieveById"]);

Route::patch("/favorites_lists/{id}",[FavoritesListController::class,'updateFavoritesList'])->middleware(['auth:sanctum','permission:update_favorites_list']);

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

