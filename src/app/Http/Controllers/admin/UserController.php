<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * 【管理者用】スタッフ一覧画面表示・
 * スタッフ別勤怠一覧画面表示用コントローラー
 */
class UserController extends Controller
{
    // スタッフ一覧画面を表示
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();
        return view('admin.user.staff_list', compact('users'));
    }
}
