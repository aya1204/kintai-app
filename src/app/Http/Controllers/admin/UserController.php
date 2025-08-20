<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

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

    // スタッフ別勤怠一覧画面を表示
    public function staff(User $user, Request $request)
    {
        // 「month」という入力があったらそれを使う(例:2025-08)
        // 入力がなければ今の年月を自動で設定
        $currentMonth = $request->input('month') ?? now()
            ->format('Y-m');

        // Carbonで月の最初と最後を計算
        $carbonMonth = Carbon::createFromFormat('Y-m', $currentMonth);
        $startOfMonth = $carbonMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonMonth->copy()->endOfMonth()->toDateString();

        // ユーザーが指定した月の勤務データを取得
        // 勤務ごとの休憩情報もまとめて一緒に読み込み
        $attendances = Work::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth]) // 指定月の全日付にマッチ(8月なら31日、9月なら30日分表示)
            ->orderBy('date') // 日付順で並べる
            ->get();
        return view('admin.user.staff_attendance_list', compact('user', 'currentMonth', 'attendances'));
    }
}
