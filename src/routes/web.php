<?php

use App\Http\Controllers\Staff\AuthController as StaffAuthController;
use App\Http\Controllers\Staff\AttendanceController as StaffAttendanceController;
use App\Http\Controllers\Staff\RequestController as StaffRequestController;
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

        // 会員登録画面表示
        Route::get('/register', [StaffAuthController::class, 'register'])->name('register');
        //会員登録処理
        Route::post('/register', [StaffAuthController::class, 'create']);
    });


    // 認証のみ必要なページ
    Route::middleware(['auth'])->group(function () {

        // 出勤登録画面表示
        Route::get('/attendance', [StaffAttendanceController::class, 'index'])->name('staff.attendance.work');

        // 勤怠一覧画面表示
        Route::get('/attendance/list', [StaffAttendanceController::class, 'attendance'])->name('staff.attendance.staff_list');

        // 勤怠詳細画面表示
        Route::get('/attendance/list/{work}', [StaffAttendanceController::class, 'show'])->name('staff.attendance.staff_detail');

        // 申請一覧画面表示
        Route::get('/stamp_correction_request/list', [StaffRequestController::class, 'applicationList'])->name('staff.request.staff_request');

        //ログアウト機能
        Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
    });