<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Work;
use App\Models\RequestWork;
use App\Models\RequestBreak;
use App\Models\Request as RequestModel;
use App\Models\User;
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
     * 勤怠詳細画面表示（既存勤怠データがある場合）
     */
    public function show($id)
    {
        // 勤怠データを取得（ユーザー情報・休憩情報も一緒に取得）
        $work = Work::with('user', 'breaks')->find($id);

        // 関連ユーザーと勤務日を取得
        $user = $work->user;
        $date = $work->date;

        // 休憩がない場合でもフォームで入力できるよう空のコレクションを準備
        $breaks = $work->breaks->count() ? $work->breaks : collect([ (object)[
            'start_time' => null,
            'end_time' => null,
        ] ]);

        return view('admin.attendance.detail', compact('work', 'user', 'date', 'breaks'));
    }

    /**
     * 勤怠新規作成画面表示
     */
    public function createForm(Request $request) {
        $userId = $request->query('user');
        $user = $userId ? User::find($userId) : null;
        $date = $request->query('date');

        $breaks = collect([ (object) [
            'start_time' => null,
            'end_time' => null,
        ]]);

        return view('admin.attendance.detail', [
            'work' => null,
            'user' => $user,
            'date' => $date,
            'breaks' => $breaks,
        ]);
    }

    /**
     * 勤怠修正
     */
    public function update(AdminAttendanceRequest $request, $workId)
    {
        // Workモデルから休憩情報を含む勤怠データを探す
        $work = Work::with('breaks')->find($workId);

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

        RequestModel::updateOrCreate(
            ['work_id' => $work->id],
            [
                'manager_id' => auth()->id(),
                'approved' => true, // 管理者直接修正なので承認済み
                'admin_remarks' => $request->input('remark'),
                'staff_remarks' => '',
            ]
        );

        return redirect()->route('admin.attendance.detail', [
            'work' => $work->id,
            'user' => $work->user_id,
            ])->with('success', '勤怠情報を更新しました');
    }

    /**
     * 勤怠新規作成申請
     */
    public function create(AdminAttendanceRequest $request)
    {
        $work = Work::create([
            'user_id' => $request->input('user_id'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);

        foreach ($request->input('breaks', []) as $break) {
            if (!empty($break['start_time']) && !empty($break['end_time'])) {
                $work->breaks()->create([
                    'start_time' => $break['start_time'],
                    'end_time' => $break['end_time'],
                ]);
            }
        }

        return redirect()->route('admin.attendance.detail', [
            'work' => $work->id,
        ])->with('success', '勤怠情報を新規作成しました');
    }
}
