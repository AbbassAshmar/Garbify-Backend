<?php

use App\Http\Controllers\FilterController;
use App\Http\Controllers\NavbarController;
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
Route::post("/register/admin" ,[UserController::class, "adminRegister"])->middleware(['auth:sanctum', 'ability:super-admin']);

// Route::middleware('token')->post('/logout',[UserController::class, 'logout']);

//needs "accept" header, token has to be in header + has to be valid for the user
Route::group(["middleware"=>["auth:sanctum"]], function(){
    Route::post('/logout',[UserController::class, 'logout']);
});


// Products Controller Routes 

Route::get("/products", [ProductController::class, "index"]);
Route::get("/products/{id}", [ProductController::class, "show"]);
Route::get("/filters",[FilterController::class, "show"]);
Route::get("/categories", [NavbarController::class, "show"]);

// Stripe Controller Routes 

Route::post("/create-checkout-session",[StripeController::class, "stripeBase"])->middleware(["auth:sanctum"]);
Route::post("/webhook", [StripeController::class,"stripeWebhookEventListener"]);


// Reviews Controller Routes 

Route::get("/products/{product_id}/reviews" , [ReviewController::class, "reviewsByProduct"]);
Route::get("/products/{product_id}/user/reviews/liked", [ReviewController::class, "likedReviewsByProduct"])->middleware(['auth:sanctum', 'ability:client,super-admin,admin']);
Route::post("/reviews/{id}/like", [ReviewController::class , "likeReviewByProduct"])->middleware(['auth:sanctum']);
Route::post("/reviews", [ReviewController::class , "createReview"])->middleware(['auth:sanctum']);

