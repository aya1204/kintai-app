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
use Illuminate\Auth\Events\Registered;

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
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        // AuthTest用：フラグがついていたらメール認証済みにする
        if (app()->environment('testing') && request()->has('skip_email_verification')) {
            // すぐログインして勤怠画面に飛ばす
            $userData['email_verified_at'] = now();
        }

        // ユーザー作成
        $user = User::create($userData);

        // メール未認証→メール認証画面へ
        event(new Registered($user));

        Auth::guard('web')->login($user);

        // Authテスト用フラグがあれば勤怠登録画面へ、本番やメール認証テストはメール認証画面へ
        if (app()->environment('testing') && request()->has('skip_email_verification'))  {
            return redirect()->route('staff.attendance.index');
        }

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
