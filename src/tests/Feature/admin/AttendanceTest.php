<?php

namespace Tests\Feature\admin;

use App\Models\Manager;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PhpParser\Node\Expr\FuncCall;
use Tests\TestCase;

/**
 * 【管理者用】勤怠一覧情報取得機能のテスト
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
     * 【管理者用】勤怠一覧情報取得機能テスト
     */

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function testShowsAllStaffAttendanceList()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを複数作成
        /** @var \App\Models\User $user */
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $work = Work::factory()->create([
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);
        }

        // 管理者ログインで勤怠一覧ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($work->start_time);
            $response->assertSee($work->end_time);
        }
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function testShowsCurrentDateOnAttendanceList()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list'));
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function testPreviousDayButtonShowsPreviousDayAttendance()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと前日の勤怠データを作成
        /** @var \App\Models\User $user */
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            Work::factory()->create([
                'user_id' => $user->id,
                'date' => now()->subDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);
        }

        // 管理者ログインで勤怠一覧ページへ
        $this->actingAs($admin, 'admin')->get(route('admin.attendance.list'));

        // 「前日」リンクをクリックした場合と同じリクエストを送信し、前日の日付を表示する
        $response = $this->get(route('admin.attendance.list', [
            'date' => now()->subDay()->toDateString()
        ]));

        // 表示される日付が前日になっていることを確認
        $response->assertSee(now()->subDay()->format('Y/m/d'));
    }

    /**
     * 「翌日」を押下した時に前の日の勤怠情報が表示される
     */
    public function testNextDayButtonShowsNextDayAttendance()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと翌日の勤怠データを作成
        /** @var \App\Models\User $user */
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            Work::factory()->create([
                'user_id' => $user->id,
                'date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);
        }

        // 管理者ログインで勤怠一覧ページへ
        $this->actingAs($admin, 'admin')->get(route('admin.attendance.list'));

        // 「翌日」リンクをクリックした場合と同じリクエストを送信し、前日の日付を表示する
        $response = $this->get(route('admin.attendance.list', [
            'date' => now()->addDay()->toDateString()
        ]));

        // 表示される日付が翌日になっていることを確認
        $response->assertSee(now()->addDay()->format('Y/m/d'));
    }
}
