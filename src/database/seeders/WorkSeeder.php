<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Work;
use App\Models\BreakTime;
use Carbon\Carbon;

class WorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 全ユーザーを取得
        $users = User::all();

        // 開始日を指定
        $start = Carbon::create(2025, 7, 1);
        $daysInMonth = $start->daysInMonth;

        // 1ヶ月分ループ
        foreach ($users as $user) {
            for ($index = 0; $index < $daysInMonth; $index++) {
                $date = $start->copy()->addDays($index);

                // 土日の場合はスキップする
                if ($date->isWeekend()) {
                    continue;
                }

                // 勤務データ作成
                $work = Work::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'start_time' => $date->copy()->setTime(9, 0),
                    'end_time' => $date->copy()->setTime(18, 0),
                ]);

                // 休憩データを作成
                BreakTime::create([
                    'work_id' => $work->id,
                    'start_time' => $date->copy()->setTime(12, 0),
                    'end_time' => $date->copy()->setTime(13, 0),
                ]);
            }
        }
    }
}
