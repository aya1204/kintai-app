<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as WorkRequest;

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
}
