<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller
{
    /**
     * メール認証画面表示
     */
    public function index()
    {
        return view('staff.auth.verify-email');
    }

    /**
     * メール認証処理
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        // ユーザーが認証済みの場合、再認証処理をせず勤怠登録画面へ遷移する
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('staff.attendance.index');
        }
        // 未認証の場合、メール認証をする
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('staff.attendance.index')->with('success', 'メール認証が完了しました。');
    }
}
