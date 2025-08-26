<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as WorkRequest;
use App\Models\Request as RequestModel;
use App\Models\Work;

/**
 * 【管理者用】申請一覧画面表示・
 * 修正申請画面表示・
 * 承認用コントローラー
 */
class RequestController extends Controller
{
    /**
     * 申請一覧画面の表示
     */
    public function applicationList(Request $request)
    {
        // ログイン済みのユーザーを表示
        $user = Auth::user();
        $tab = $request->input('tab', 'wait');

        // requestsテーブルから検索し、リレーション経由でrequest_works→userを読み込む
        $requests = WorkRequest::with('requestWork.user')
            ->when($tab === 'wait', fn($q) => $q->where('approved', false))
            ->when($tab === 'clear', fn($q) => $q->where('approved', true))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.request.list', compact('user', 'tab', 'requests'));
    }

    /**
     * 修正申請承認画面の表示
     */
    public function fix($id)
    {
        $request = RequestModel::with(['work.user', 'requestWork.user'])->findOrFail($id);

        // 勤怠データがある場合はworkから、修正申請だけの場合はrequestWorkから取得
        $work = $request->work;
        $user = $work ? $work->user : $request->requestWork->user;
        $date = $work ? $work->date : $request->requestWork->date;

        // Bladeで参照する変数を揃えて渡す
        $requestWork = $request->requestWork;
        $approved = $request->approved;

        return view('admin.request.approval', [
            'request' => $request,
            'user' => $request->requestWork->user,
            'work' => $request->work,
            'requestWork' => $request->requestWork,
            'date' => $request->date,
            'approved' => $approved,
        ]);
    }

    /**
     * 承認処理
     */
    public function approval($id)
    {
        $approvalRequest = WorkRequest::with('requestWork.requestBreaks')->findOrFail($id);

        // ステータス更新
        $approvalRequest->approved = true;
        $approvalRequest->save();

        $requestWork = $approvalRequest->requestWork;

        // work_idがあれば既存勤怠を更新、なければ新規作成
        if ($requestWork->work_id) {
            // 既存の勤怠を更新
            $work = Work::findOrFail($requestWork->work_id);
            $work->update([
                'date' => $requestWork->date,
                'start_time' => $requestWork->start_time,
                'end_time' => $requestWork->end_time,
                'remark' => $requestWork->remark,
            ]);
        } else {
            // すでに同じuser_id + dateが存在するか確認
            $work = Work::where('user_id', $requestWork->user_id)
            ->where('date', $requestWork->date)
            ->first();

            if ($work) {
                $work->update([
                    'start_time' => $requestWork->start_time,
                    'end_time' => $requestWork->end_time,
                    'remark' => $requestWork->remark,
                ]);
            } else {
                $work = Work::create([
                    'user_id' => $requestWork->user_id,
                    'date' => $requestWork->date,
                    'start_time' => $requestWork->start_time,
                    'end_time' => $requestWork->end_time,
                    'remark' => $requestWork->remark,
                ]);
            }

            // 作成したwork_idをrequest_worksに保存
            $approvalRequest->update(['work_id' => $work->id]);
            }

        // 休憩時間を反映
        $work->breaks()->delete();
        foreach ($requestWork->requestBreaks as $requestBreak) {
            $work->breaks()->create([
                'start_time' => $requestBreak->start_time,
                'end_time' => $requestBreak->end_time,
            ]);
        }

        return redirect()->back()->with('success', '承認しました');
    }
}
