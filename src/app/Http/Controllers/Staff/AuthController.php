<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;

/**
 * 会員登録、ログイン、ログアウト用のコントローラー
 */
class AuthController extends Controller
{
    // 会員登録画面表示
    public function register(Request $request)
    {
        return view('staff.auth.register');
    }

    // 会員登録機能
    public function create(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        Auth::login($user);

        return redirect('/attendance');
    }
}
