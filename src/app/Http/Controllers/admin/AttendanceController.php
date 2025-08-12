<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Work;

/**
 * 【管理者用】スタッフの勤怠一覧画面表示・
 * 勤怠詳細画面表示・
 * 勤怠修正用コントローラー
 */
class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面表示
     */
    public function attendance(Request $request)
    {
        // 認証済みの管理者情報を取得
        $user = Auth::user();

        // 「date」という入力があったらそれを使う(例:2025-08-01)
        // 入力がなければ今の日付を自動で設定
        $currentDate = $request->input('date') ?? now()
            ->format('Y-m-d');

        // ユーザーの1日ごとの勤務データを取得
        // 勤務ごとの休憩情報もまとめて一緒に読み込み
        $attendances = Work::with('breaks')
            ->where('date', $currentDate)
            ->get();

        return view('admin.attendance.list', compact('attendances', 'currentDate'));
    }

    /**
     * 勤怠詳細画面表示
     */
    public function show($id, Request $request)
    {
        $work = Work::with(['user', 'breaks'])->find($id);

        // $workがあればそのuser、なければ認証中のユーザー
        $user = $work ? $work->user : Auth::user();
        // URLのクエリから ?date=2025-08-01 のような日付を取得（なければnull）
        $date = $request->input('date');
        return view('admin.attendance.detail', compact('work', 'user', 'date'));
    }
}
