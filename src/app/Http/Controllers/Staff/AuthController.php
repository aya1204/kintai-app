<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * 会員登録、ログイン、ログアウト用のコントローラー
 */
class AuthController extends Controller
{
    public function register(Request $request)
    {
        return view('staff.auth.register');
    }
}
