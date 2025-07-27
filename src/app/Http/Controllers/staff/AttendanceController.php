<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Work;
use App\Models\BreakTime;
use Carbon\Carbon;

/**
 * 出勤・休憩・退勤登録、勤怠一覧、勤怠詳細のコントローラー
 */
class AttendanceController extends Controller
{
    /**
     * ログイン中のユーザーがどの勤務状態(status)にいるか判断する
     */
    private function getCurrentStatus(): string
    {
        $user = Auth::user();

        // 今日すでに出勤済みか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->latest('start_time')
            ->first();

        // もし出勤記録がなかったら「出勤前」と判断する
        if (!$todayWork) {
            return 'before_work'; // 出勤前
        }

        // もし出勤記録があり、end_timeがあったら「退勤済み」と判断する
        if ($todayWork->end_time) {
            return 'after_work';
        }

        // 休憩記録からまだ終わっていない休憩がないか確認
        $onBreak = BreakTime::where('work_id', $todayWork->id)
            ->whereNull('end_time')
            ->exists();

        // 上の結果に応じて「休憩中」か「勤務中」か判断して返す
        return $onBreak ? 'on_break' : 'working';
    }
}
