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

    /**
     * 出勤登録画面の表示
     */
    public function index()
    {
        // 仮のステータス判定
        $status = $this->getCurrentStatus();

        return view('staff.attendance.work', compact('status'));
    }

    /**
     * 出勤ボタン処理
     */
    public function workStart(Request $request)
    {
        $user = Auth::user();

        $today = now()->toDateString();

        // すでに出勤済みか確認
        $alreadyWorking = Work::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

        if ($alreadyWorking) {
            return redirect()->route('staff.attendance.index')->with('error', 'すでに出勤済みです。');
        }

        // 出勤登録処理
        Work::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => now(),
        ]);

        return redirect()->route('staff.attendance.index')->with('success', '出勤しました。');
    }

    /**
     * 休憩入ボタン処理
     */
    public function takeBreak(Request $request)
    {
        $user = Auth::user();

        // 今日出勤しているか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->latest('start_time')
            ->first();

        // もし出勤登録していないか退勤済みの場合
        if (!$todayWork || $todayWork->end_time) {
            return redirect()->route('staff.attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        // 休憩中ではないことを確認（直近のbreakにend_timeがないなら中断する）
        $alreadyOnBreak = BreakTime::where('work_id', $todayWork->id)
            ->whereNull('end_time')->exists();

        if ($alreadyOnBreak) {
            return redirect()->route('staff.attendance.index')->with('error', 'すでに休憩中です。');
        }

        // 休憩登録処理
        BreakTime::create([
            'work_id' => $todayWork->id,
            'start_time' => now(),
        ]);

        return redirect()->route('staff.attendance.index')->with('success', '休憩に入りました。');
    }

    /**
     * 休憩戻ボタン処理
     */
    public function breakReturn(Request $request)
    {
        $user = Auth::user();

        // 今日出勤しているか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->latest('start_time')
            ->first();

        // もし出勤登録していないか退勤済みの場合
        if (!$todayWork) {
            return redirect()->route('staff.attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        // 直近で未終了の休憩を取得
        $break = BreakTime::where('work_id', $todayWork->id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        // もし未終了の休憩がなかったら中断
        if (!$break) {
            return redirect()->route('staff.attendance.index')->with('error', '休憩中ではありません。');
        }

        // 休憩戻り更新処理
        $break->update([
            'end_time' => now(),
        ]);

        return redirect()->route('staff.attendance.index')->with('success', '休憩から戻りました。');
    }

    /**
     * 退勤ボタン処理
     */
    public function workEnd(Request $request)
    {
        $user = Auth::user();

        // すでに出勤済みか、退勤済みではないか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        // もし勤務記録がなかったら中断
        if (!$todayWork) {
            return redirect()->route('staff.attendance.index')->with('error', '勤務記録が見つかりません。');
        }

        // 退勤登録処理
        $todayWork->update([
            'end_time' => now(),
        ]);

        return redirect()->route('staff.attendance.index')->with('success', '退勤しました。');
    }
}
