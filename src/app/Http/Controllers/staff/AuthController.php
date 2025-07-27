<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

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
            'password' =>  Hash::make($request->password),
        ]);

        Auth::guard('web')->login($user);

        return redirect('/attendance');
    }

    // ログイン画面を表示
    public function index(Request $request)
    {
        return view('staff.auth.login');
    }

    // ログイン処理
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

        return redirect('/attendance');
    }

    return back()->withErrors([
        'email' => 'ログイン情報が登録されていません',
    ]);
    }
}
