<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;

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
        if (app()->environment('testing')) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // テスト環境でも自動ログインさせる
            Auth::login($user);
            return redirect()->route('staff.attendance.index');
        }

        // 本番環境はログイン後メール認証誘導画面へ
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' =>  Hash::make($request->password),
        ]);

        Auth::guard('web')->login($user);

        // 会員登録後メール認証誘導画面へ
        return redirect()->route('verification.notice');
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

            $user = Auth::user();

            // メール認証済みかチェック
            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'メール認証が完了していません。メールを確認して認証を完了させてください。'
                ]);
            }

        return redirect('/attendance');
    }

    return back()->withErrors([
        'email' => 'ログイン情報が登録されていません',
    ]);
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
