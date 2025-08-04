<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Work;
use App\Models\BreakTime;
use App\Models\RequestWork;
use App\Models\RequestBreak;
use App\Models\Request as RequestModel;
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

        $today = now()->toDateString();

        // 今日すでに出勤済みか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('date', today())
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
        $today = now()->toDateString();

        // 今日出勤しているか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('date', $today)
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
        $today = now()->toDateString();

        // 今日出勤しているか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('date', $today)
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
        $today = now()->toDateString();

        // すでに出勤済みか、退勤済みではないか確認
        $todayWork = Work::where('user_id', $user->id)
            ->whereDate('date', $today)
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

    /**
     * 勤怠一覧画面表示
     */
    public function attendance(Request $request)
    {
        $user = Auth::user();
        $currentMonth = $request->input('month') ?? now()
->format('Y-m');

        $attendances = Work::with('breaks')
        ->where('user_id', $user->id)
        ->where('date', 'like', "{$currentMonth}%")
        ->orderBy('date')
        ->get();

        return view('staff.attendance.list', compact('attendances', 'currentMonth'));
    }

    /**
     * 勤怠一覧画面表示
     */
    public function show($workId)
    {
        if ($workId === '0') {
            $work = new Work();
            $work->id = 0;
            $work->start_time = null;
            $work->end_time = null;
            $work->breaks = collect();
            $name = '';
        } else {
            $work = Work::with(['breaks', 'user'])->findOrFail($workId);
            $name = $work->user ? $work->user->name: '';
        }
            return view('staff.attendance.detail', compact('work', 'name'));
        }

        /**
         * 勤怠修正申請
         */
        public function requestCorrection(AttendanceRequest $request,$workId)
        {
            $user = auth()->user();
            // 申請対象の勤怠データ
            $work = Work::with('breaks', 'user')->findOrFail(($workId));

            $requestWork = RequestWork::updateOrCreate([
                'user_id' => $user->id,
                'date' => $work->date, //勤務日
            ],
            [
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
            ]);

            foreach ($request->input('breaks', []) as $break) {
                if (!empty($break['start_time']) && !empty($break['end_time'])) {
                    RequestBreak::create([
                        'request_work_id' => $requestWork->id,
                        'date' => $work->date, //勤務日
                        'start_time' => $break['start_time'],
                        'end_time' => $break['end_time'],
                    ]);
                }
            }

            // requestsテーブルに保存（承認状態は仮でfalse、備考も記入）
            RequestModel::create([
                'work_id' => $work->id,
                'manager_id' => 1,
                //todo 'manager_id' => auth()->guard('admin')->id(),
                'approved' => false,
                'staff_remarks' => $request->input('remark'),
                'admin_remarks' => '', // 管理者が後で記入
            ]);

            return redirect()->route('staff.attendance.detail', ['work' => $work->id])->with('success', '修正申請が送信されました');
        }
}
