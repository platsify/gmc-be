<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('auth/user', function (Request $request) {
    return $request->user();
});
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/logout', [AuthController::class, 'logout']);

Route::post('delete-many-product', [ProductController::class, 'deleteManyProducts']);
Route::resource('product', ProductController::class);

Route::apiResource('shop', ShopController::class);

Route::get('project/repush', [ProjectController::class, 'repush']);
Route::get('project/remap', [ProjectController::class, 'mapNewProduct']);
Route::apiResource('project', ProjectController::class);

// Category
Route::get('category/by-shop/{shopId}', [CategoryController::class, 'getCategoryByShop']);
Route::apiResource('category', CategoryController::class);

// Custom field
Route::get('custom-field/by-project/{shopId}', [CustomFieldController::class, 'getCustomFieldByProject']);
Route::apiResource('custom-field', CustomFieldController::class);

Route::get('shop/sync_now/{shopId}', [ShopController::class, 'syncNow']);
