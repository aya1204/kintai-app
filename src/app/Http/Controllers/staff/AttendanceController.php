<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * 出勤・休憩・退勤登録、勤怠一覧、勤怠詳細のコントローラー
 */
class AttendanceController extends Controller
{
    // 勤怠登録画面表示
    public function index(Request $request)
    {
        return view('staff.attendance.work');
    }
}
