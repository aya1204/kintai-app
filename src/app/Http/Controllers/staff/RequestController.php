<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as WorkRequest;

class RequestController extends Controller
{
    /**
     * 申請一覧画面の表示
     */
    public function requestList(Request $request)
    {
        // ログイン済みのユーザーを表示
        $user = Auth::user();
        $tab = $request->input('tab', 'wait');

        // requestsテーブルから検索し、リレーション経由でrequest_works→userを読み込む
        $requests = WorkRequest::with('requestWork.user')
        ->when($tab === 'wait', fn($q) => $q->where('approved', false))
        ->when($tab === 'clear', fn ($q) => $q->where('approved', true))
        ->orderByDesc('created_at')
        ->get();

        return view('staff.request.list', compact('user', 'tab', 'requests'));
    }
}
