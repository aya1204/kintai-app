<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as WorkRequest;
use App\Models\Request as RequestModel;

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
        $approved = $request->status === 'approved';

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
    public function approval(Request $request, $id)
    {
        $workRequest = WorkRequest::findOrFail($id);

        $workRequest->update([
            'approved' => true,
            'admin_remarks' => $request->admin_remarks,
        ]);

        return redirect()->route('admin.request.list', ['tab' => 'clear'])->with('success', '申請を承認しました');
        // $workRequest = WorkRequest::with('work', 'requestWork')->findOrFail($id);

        // // フォームから送られてきた入力値を取得
        // $remark = $request->input('remark');
        // $startTime = $request->input('start_time');
        // $endTime = $request->input('end_time');
        // $breaks = $request->input('breaks', []);

        // // 承認状態に更新
        // $workRequest->approved = true;
        // $workRequest->admin_remarks = $request->input('remark', '');
        // $workRequest->manager_id = Auth::id();
        // $workRequest->save();
        // return redirect()->route('admin.request.list', ['tab' => 'clear'])->with('success', '勤怠申請を承認しました。');
    }
}
