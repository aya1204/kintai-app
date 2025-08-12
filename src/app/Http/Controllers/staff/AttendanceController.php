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
use PhpParser\Node\Expr\FuncCall;

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
        // 認証済みのユーザー情報を取得
        $user = Auth::user();

        // 「month」という入力があったらそれを使う(例:2025-08)
        // 入力がなければ今の年月を自動で設定
        $currentMonth = $request->input('month') ?? now()
->format('Y-m');

        // ユーザーが指定した月の勤務データを取得
        // 勤務ごとの休憩情報もまとめて一緒に読み込み
        $attendances = Work::with('breaks')
        ->where('user_id', $user->id)
        ->where('date', 'like', "{$currentMonth}%") // 指定月の全日付にマッチ(8月なら31日、9月なら30日分表示)
        ->orderBy('date') // 日付順で並べる
        ->get();

        return view('staff.attendance.list', compact('attendances', 'currentMonth'));
    }

    /**
     * 勤怠新規作成画面（勤怠詳細画面と一緒）
     */
    public function createForm(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->toDateString());
        $work = null;
        $requestWork = null;
        return view('staff.attendance.detail', compact('user', 'date', 'work', 'requestWork'));
    }

    /**
     * 勤怠詳細画面表示
     */
    public function show($id, Request $request)
    {
        $work = Work::with(['user', 'breaks'])->find($id);

        // $workがない場合は新規作成画面を表示
        if (!$work) {
            return redirect()->route('staff.attendance.createForm', ['date' => $request->input('date')]);
        }

        // $workがあればそのuser
        $user = $work->user;

        $requestModel = RequestModel::where('work_id', $work->id)->first();

        $requestWork = null;
        if ($requestModel) {
            // RequestModelのrequest_work_idに紐づくRequestWorkを取得
            $requestWork = RequestWork::find($requestModel->request_work_id);
        }

        // 表示用の日付
        $date = $work->date;
            return view('staff.attendance.detail', compact('work', 'user', 'date', 'requestWork'));
        }

    /**
     * 勤怠新規作成申請
     */
    public function createCorrection(AttendanceRequest $request)
    {
        // ログイン中のユーザー情報を取得
        $user = auth()->user();

        // 勤務日を取得(hiddenでフォームに渡している日付または現在の日付)
        $date = $request->input('date', now()->toDateString());

        // request_worksテーブルに新しい修正申請レコードを作成
        $requestWork = RequestWork::Create([
            'user_id' => $user->id, // ユーザーIDをセット
            'date' => $date, //勤務日
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);

        // 休憩時間の修正申請データを作成
        foreach ($request->input('breaks', []) as $break) {
            // 休憩開始・終了時間が両方とも空でなければ処理
            if (!empty($break['start_time']) && !empty($break['end_time'])) {
                RequestBreak::create([
                    'request_work_id' => $requestWork->id, // 修正申請の勤怠IDと紐付け
                    'date' => $date, //勤務日
                    'start_time' => $break['start_time'],
                    'end_time' => $break['end_time'],
                ]);
            }
        }

        // requestsテーブルに保存（承認状態は仮でfalse、備考も記入）
        RequestModel::create([
            'request_work_id' => $requestWork->id,
            'manager_id' => 1,
            //todo 'manager_id' => auth()->guard('admin')->id(),
            'approved' => false,
            'staff_remarks' => $request->input('remark'),
            'admin_remarks' => '', // 管理者が後で記入
        ]);

        // 勤怠一覧画面へ遷移
        return redirect()->route('staff.attendance.list', [
            'date' => $date,
        ])->with('success', '新規修正申請が送信されました');
    }

    /**
     * 勤怠修正申請
     */
    public function requestCorrection(AttendanceRequest $request,$workId)
    {
        // フォームから送られてきた勤務日を取得
        $date = $request->input('date');

        // ログイン済みユーザー情報を取得
        $user = auth()->user();

        // 修正申請対象の勤怠データをIDから取得
        // その勤怠の休憩情報とユーザー情報も一緒に取得
        $work = Work::with('breaks', 'user')->findOrFail(($workId));

        // request_worksテーブルに修正申請データを保存
        // 同じユーザーと同じ勤務日なら更新、なければ新規作成
        $requestWork = RequestWork::updateOrCreate([
            'user_id' => $user->id,
            'date' => $work->date, //勤務日
        ],
        [
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);

        // 休憩時間の修正申請があれば、1件ずつrequest_breaksテーブルに保存
        foreach ($request->input('breaks', []) as $break) {
            // 休憩開始・休憩終了が両方とも空じゃない場合のみ登録
            if (!empty($break['start_time']) && !empty($break['end_time'])) {
                RequestBreak::create([
                    'request_work_id' => $requestWork->id, // 修正申請の勤怠IDと紐付ける
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

        return redirect()->route('staff.attendance.detail', [
            'user' => $user,
            'work' => $work->id,
            'date' => $date,
            ])->with('success', '修正申請が送信されました');
        }
}
