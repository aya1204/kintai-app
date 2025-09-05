<?php

namespace Tests\Feature\staff;

use App\Models\User;
use App\Models\Work;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 【スタッフ用】勤怠一覧・勤怠詳細情報取得のテスト
 */
class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * 勤怠一覧情報取得機能テスト
     */

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function testUserCanSeeAllOwnAttendanceRecords()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤と退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 休憩入りと休憩戻りデータを作成
        $break = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.list', [
            'month' => now()->format('Y-m')
        ]));

        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 休憩時間の合計
        $response->assertSee('1:00');
        // 勤務時間の合計
        $response->assertSee('8:00');
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function testAttendanceListShowsCurrentMonth()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.list', [
            'month' => now()->format('Y-m')
        ]));

        $response->assertSee(now()->format('Y/m'));
    }

    /**
     * 「前月」ボタンを押したら表示月の前月の情報が表示される
     */
    public function testAttendanceListShowsPreviousMonth()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 前月にデータを作成
        $previousMonth = now()->subMonth()->format('Y-m-d');
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => $previousMonth,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 休憩入りと休憩戻りデータを作成
        $break = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($user);

        // 前月ボタンを押す
        $response = $this->get(route('staff.attendance.list', [
            'month' => now()->subMonth()->format('Y-m')
        ]));

        $response->assertSee(now()->subMonth()->format('Y/m'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 休憩時間の合計
        $response->assertSee('1:00');
        // 勤務時間の合計
        $response->assertSee('8:00');
    }

    /**
     * 「翌月」ボタンを押したら表示月の翌月の情報が表示される
     */
    public function testAttendanceListShowsNextMonth()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 翌月にデータを作成
        $nextMonth = now()->addMonth()->format('Y-m-d');
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => $nextMonth,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 休憩入りと休憩戻りデータを作成
        $break = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($user);

        // 翌月ボタンを押す
        $response = $this->get(route('staff.attendance.list', [
            'month' => now()->addMonth()->format('Y-m')
        ]));

        $response->assertSee(now()->addMonth()->format('Y/m'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 休憩時間の合計
        $response->assertSee('1:00');
        // 勤務時間の合計
        $response->assertSee('8:00');
    }

    /**
     * 「詳細」を押すとその日の勤怠詳細画面へ遷移する
     */
    public function testAttendanceDetailButtonNavigatesToDetailPage()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $break = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.detail', ['work' => $work->id]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }


    /**
     * 勤怠詳細情報取得機能テスト
     */

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function testAttendanceDetailShowsUserName()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]
        );

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHours(8)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.detail', $work->id));
        // テストユーザー」と表示されているか確認する
        $response->assertSee('テストユーザー');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function testAttendanceDetailShowsSelectedDate()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour(8)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 「〇〇年」と「〇月〇日（0なし）」と表示されるか確認
        $response = $this->get(route('staff.attendance.detail', $work->id));
        // 「〇〇〇〇年」と表示されるか確認
        $response->assertSee(now()->format('Y年'));
        // 「〇月〇日（0なし）」と表示されるか確認
        $response->assertSee(now()->format('n月j日'));
    }

    /**
     * 「出勤・退勤」の時間がログインユーザーの打刻と一致している
     */
    public function testAttendanceDetailShowsWorkTimes()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤時間を定義
        $start = now()->setTime(9,0,0);
        // 退勤時間を定義
        $end = now()->setTime(18,0,0);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.detail', $work->id));
        // 出勤時間「9:00」が表示されているか確認
        $response->assertSee($start->format('H:i'));
        // 退勤時間「18:00」が表示されているか確認
        $response->assertSee($end->format('H:i'));
    }

    /**
     * 「休憩」の時間がログインユーザーの打刻と一致している
     */
    public function testAttendanceDetailShowsBreakTimes()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour(8)->format('H:i:s'),
        ]);

        // 休憩入り時間を定義
        $start = now()->setTime(12,0,0);
        // 休憩戻り時間を定義
        $end = now()->setTime(13,0,0);

        // 休憩入り・休憩戻りデータを作成
        $break = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.detail', $work->id));

        // 休憩入り時間「12:00」が表示されているか確認
        $response->assertSee('12:00');
        // 休憩戻り時間「13:00」が表示されているか確認
        $response->assertSee('13:00');
    }
}
