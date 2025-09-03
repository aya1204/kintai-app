<?php

namespace Tests\Feature\staff;

use App\Models\User;
use App\Models\Work;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 勤怠一覧・勤怠詳細情報取得のテスト
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
}
