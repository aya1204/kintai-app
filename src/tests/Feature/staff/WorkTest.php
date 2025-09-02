<?php

namespace Tests\Feature\staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class WorkTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * 日時取得機能テスト
     */
    public function testCurrentDatetimeIsFormattedCorrectly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.index'));

        $today = date('Y年m月d日');
        $response->assertSee($today);
    }

    /**
     * 勤務外ステータスが正しく表示されるテスト
     */
    public function testStatusIsBeforeWork()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.index'));

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中ステータスが正しく表示されるテスト
     */
    public function testStatusIsWorking()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        // 出勤状態にする（Workレコードを作成）
        $user->works()->create([
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('staff.attendance.index'));

        $response->assertSee('出勤中');
    }

    /**
     * 休憩中ステータスが正しく表示されるテスト
     */
    public function testStatusIsOnBreak()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        // 出勤状態にする（Workレコードを作成）
        $work = $user->works()->create([
            'date' => now()->toDateString(),
            'start_time' => now()->subHours(1),
        ]);

        // 休憩状態にする
        $work->breaks()->create([
            'start_time' => now()->subMinutes(30),
        ]);

        $this->actingAs($user);
        $response = $this->get(route('staff.attendance.index'));

        $response->assertSee('休憩中');
    }

    /**
     * 退勤ステータスが正しく表示されるテスト
     */
    public function testStatusIsWorkout()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->works()->create([
            'date' => now()->toDateString(),
            'start_time' => now()->subHours(8),
            'end_time' => now()->subHours(1),
        ]);

        $this->actingAs($user);
        $response = $this->get(route('staff.attendance.index'));

        $response->assertSee('退勤済');
    }
}
