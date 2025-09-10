<?php

namespace Tests\Feature\admin;

use App\Models\BreakTime;
use App\Models\Manager;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PhpParser\Node\Expr\FuncCall;
use Tests\TestCase;

/**
 * 【管理者用】ユーザー情報取得機能テスト
 */
class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function testAdminCanSeeAllStaffsNameAndEmail()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        /** @var \App\Models\User $user */
        $users = User::factory()->count(3)->create();

        // 管理者としてログインした後、スタッフ一覧画面へ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 全スタッフの名前とメールアドレスが確認できる
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function testAdminCanSeeSelectedStaffAttendanceData()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $dates = [
            now()->subDay()->format('Y-m-d'),
            now()->toDateString(),
            now()->addDay()->format('Y-m-d'),
        ];

        foreach ($dates as $date) {
            $work = Work::factory()->create([
                'user_id' => $user->id,
                'date' => $date,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);

            BreakTime::factory()->create([
                'work_id' => $work->id,
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);
        }

        // 管理者にログインしてスタッフ一覧ページへ
        $this->actingAs($admin, 'admin')->get(route('admin.staff.list'));

        // スタッフ一覧ページの詳細を押して各スタッフの勤怠一覧ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance.list', $user));
        $response->assertStatus(200);

        // 各日付が表示されていることを確認
        $response->assertSee(now()->subDay()->format('m/d'));
        $response->assertSee(now()->format('m/d'));
        $response->assertSee(now()->addDay()->format('m/d'));

        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 休憩時間の合計
        $response->assertSee('1:00');
        // 勤務時間の合計
        $response->assertSee('8:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function testAttendanceListShowsPreviousMonth()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 前月に勤怠データを作成
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

        // スタッフ一覧ページの前月ボタンを押す
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance.list', [
            'user' => $user->id,
            'month' => now()->subMonth()->format('Y-m'),
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
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function testAttendanceListShowsNextMonth()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 翌月に勤怠データを作成
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

        // スタッフ勤怠一覧ページの翌月ボタンを押す
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance.list', [
            'user' => $user->id,
            'month' => now()->addMonth()->format('Y-m'),
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
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function testStaffsAttendanceDetailButtonNavigatesToDetailPage()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

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

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.detail', [
            'user' => $user->id,
            'work' => $work->id
        ]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
