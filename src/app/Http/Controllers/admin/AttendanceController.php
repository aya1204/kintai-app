<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Work;
use App\Models\RequestWork;
use App\Models\RequestBreak;
use App\Models\Request as RequestModel;
use App\Http\Requests\AdminAttendanceRequest;

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

    /**
     * 勤怠修正
     */
    public function update(AdminAttendanceRequest $request, $workId)
    {
        // Workモデルから休憩情報を含む勤怠データを探す
        $work = Work::with('breaks')->findOrFail($workId);

        if ($request->filled('date')) {
            $work->date = $request->input('date');
        }

        // 勤怠情報を更新
        $work->start_time = $request->input('start_time');
        $work->end_time = $request->input('end_time');
        $work->save();

        // 休憩を一旦削除
        $work->breaks()->delete();

        // フォームの休憩データを再登録
        foreach (array_values($request->input('breaks', [])) as $break) {
            if (!empty($break['start_time']) && !empty($break['end_time'])) {
                $work->breaks()->create([
                    'start_time' => $break['start_time'],
                    'end_time' => $break['end_time'],
                ]);
            }
        }

        $requestRecord = RequestModel::updateOrCreate(
            ['work_id' => $work->id],
            [
                'manager_id' => auth()->id(),
                'approved' => true, // 管理者直接修正なので承認済み
                'admin_remarks' => $request->input('remark'),
                'staff_remarks' => '',
            ]
            );

        return redirect()->route('admin.attendance.detail', ['work' => $work->id])->with('success', '勤怠情報を更新しました');
    }

    /**
     * 勤怠新規作成申請
     */
    public function createCorrection(AdminAttendanceRequest $request)
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
        return redirect()->route('admin.attendance.list', [
            'date' => $date,
        ])->with('success', '新規修正申請が送信されました');
    }
}
