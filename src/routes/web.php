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
Route::middleware('guest:web')->group(function () {

    // 会員登録画面表示
    Route::get('/register', [StaffAuthController::class, 'register'])->name('register');
    //会員登録処理
    Route::post('/register', [StaffAuthController::class, 'create']);

    // ログイン画面表示
    Route::get('/login', [StaffAuthController::class, 'index'])->name('login');
    // ログイン処理
    Route::post('/login', [StaffAuthController::class, 'login']);
});


// 認証のみ必要なページ
Route::middleware('auth:web')->name('staff.')->group(function () {

    // 出勤登録画面表示
    Route::get('/attendance', [StaffAttendanceController::class, 'index'])->name('attendance.index');
    // 出勤登録
    Route::post('/attendance/work-start', [StaffAttendanceController::class, 'workStart'])->name('attendance.workStart');
    // 休憩入 登録
    Route::post('/attendance/take-break', [StaffAttendanceController::class, 'takeBreak'])->name('attendance.takeBreak');
    // 休憩戻 登録
    Route::post('/attendance/break-return', [StaffAttendanceController::class, 'breakReturn'])->name('attendance.breakReturn');
    // 退勤登録
    Route::post('/attendance/work-end', [StaffAttendanceController::class, 'workEnd'])->name('attendance.workEnd');

    // 勤怠一覧画面表示
    Route::get('/attendance/list', [StaffAttendanceController::class, 'attendance'])->name('attendance.list');

    // 勤怠詳細画面表示
    Route::get('/attendance/{work}', [StaffAttendanceController::class, 'show'])->name('attendance.detail');
    // 勤怠修正
    Route::post('/attendance/correction/{work}', [StaffAttendanceController::class, 'requestCorrection'])->name('attendance.request');
    // 勤怠データ新規作成（修正画面にて）
    Route::post('/attendance/correction', [StaffAttendanceController::class, 'createCorrection'])->name('attendance.create');

    // 申請一覧画面表示
    Route::get('/stamp_correction_request/list', [StaffRequestController::class, 'applicationList'])->name('request');

    //ログアウト機能
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');
});
