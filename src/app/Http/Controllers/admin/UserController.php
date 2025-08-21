<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

/**
 * 【管理者用】スタッフ一覧画面表示・
 * スタッフ別勤怠一覧画面表示用コントローラー
 */
class UserController extends Controller
{
    /**
     * スタッフ一覧画面を表示
     */
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();
        return view('admin.user.staff_list', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧画面を表示
     */
    public function staff(User $user, Request $request)
    {
        // 「month」という入力があったらそれを使う(例:2025-08)
        // 入力がなければ今の年月を自動で設定
        $currentMonth = $request->input('month') ?? now()
            ->format('Y-m');

        // Carbonで月の最初と最後を計算
        $carbonMonth = Carbon::createFromFormat('Y-m', $currentMonth);
        $startOfMonth = $carbonMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonMonth->copy()->endOfMonth()->toDateString();

        // ユーザーが指定した月の勤務データを取得
        // 勤務ごとの休憩情報もまとめて一緒に読み込み
        $attendances = Work::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth]) // 指定月の全日付にマッチ(8月なら31日、9月なら30日分表示)
            ->orderBy('date') // 日付順で並べる
            ->get();
        return view('admin.user.staff_attendance_list', compact('user', 'currentMonth', 'attendances'));
    }

    /**
     * CSV出力
     */
    public function csvExport(User $user, Request $request)
    {
        // 対象年月を取得（例: "2025-08"）
        $month = $request->input('month') ?? now()->format('Y-m');

        // Carbonで月の最初と最後を計算
        $carbonMonth = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $carbonMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonMonth->copy()->endOfMonth()->toDateString();

        // 指定ユーザーの勤怠データを取得（休憩も一緒に取得）
        $attendances = Work::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date')
            ->get();

        // CSVの1行目（ヘッダー行）
        $csvData[] = ['日付', '出勤', '退勤', '休憩合計', '勤務時間合計'];

        // 勤怠データを1日ごとに整形してCSV用データに追加
        foreach ($attendances as $attendance) {
            // 休憩時間合計（分単位）
            $breakMinutes = $attendance->breaks->sum(function ($break) {
                return Carbon::parse($break->start_time)->diffInMinutes(Carbon::parse($break->end_time));
            });

            // 勤務時間合計（出勤〜退勤 - 休憩時間）
            $workMinutes = $attendance->start_time && $attendance->end_time ? Carbon::parse($attendance->start_time)->diffInMinutes(Carbon::parse($attendance->end_time)) - $breakMinutes : 0;

            // 1日分をCSVに追加
            $csvData[] = [
                $attendance->date,
                $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '', // 出勤
                $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '', // 退勤
                $breakMinutes > 0 ? floor($breakMinutes/60).':'.str_pad($breakMinutes%60,2,'0',STR_PAD_LEFT) : '', // 休憩時間合計（hh:mm形式）
                $workMinutes > 0 ? floor($workMinutes / 60) . ':' . str_pad($breakMinutes % 60, 2, '0', STR_PAD_LEFT) : '', // 勤務時間合計（hh:mm形式）
            ];
        }

        // ダウンロードするCSVファイル名（例: "山田太郎_2025_08_attendance.csv"）
        $filename = "{$user->name}_{$carbonMonth->format('Y_m')}_attendance.csv";
        $handle = fopen('php://temp', 'r+');

        // 一時的にファイルをメモリ上の php://tempに書き込み（サーバーに不要なファイルを残さずCSV生成する）
        foreach ($csvData as $line) {
            fputcsv($handle, $line); // 配列をCSV形式で1行書き込む
        }

        rewind($handle); // データを最初から読み込むため、ファイルポインタを先頭に戻す
        $csv = stream_get_contents($handle); // 文字列としてCSV全体を読み込む
        fclose($handle);

        // CSVデータをHTTPレスポンスで返却→ブラウザがダウンロードを開始する
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
}
