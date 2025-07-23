<?php

use App\Http\Controllers\Staff\AuthController as StaffAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// スタッフ用のルーティング

    // 未ログイン時（ゲスト）
    Route::middleware('guest')->group(function () {
        Route::get('/register', [StaffAuthController::class, 'register'])->name('register');
    });