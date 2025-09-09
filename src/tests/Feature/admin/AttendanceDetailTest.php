<?php

namespace Tests\Feature\admin;

use App\Models\Manager;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 【管理者用】勤怠詳細・勤怠詳細情報取得のテスト
 */
class AttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function testSelectedDataIsDisplayed()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを複数作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $work1 = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $work2 = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '19:00',
        ]);

        // 管理者ログインで勤怠詳細ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.detail', $work1->id));

        // work1の情報が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // work2の情報は表示されていないことを確認
        $response->assertDontSee($work2->date);
        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationFailsWhenStartTimeIsAfterEndTime()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを複数作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者ログインで勤怠詳細ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.detail', $work->id));
        $response->assertStatus(200);

        $response = $this->from(route('admin.attendance.detail', $work->id))
            ->post(route('admin.attendance.request', $work->id), [
                'start_time' => '18:00',
                'end_time' => '09:00',
                'remark' => 'テスト申請',
            ]);

        $response->assertRedirect(route('admin.attendance.detail', $work->id));

        $this->followingRedirects()
            ->get(route('admin.attendance.detail', $work->id))
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testBreakStartTimeCannotBeAfterEndTime()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者にログインして勤怠詳細ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.detail', $work->id));

        // 休憩開始時間が退勤時間より遅くなるよう設定して修正申請する
        $response = $this->from(route('admin.attendance.detail', $work->id))
        ->post(route('admin.attendance.request', $work->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remark' => 'テスト申請',
            'breaks' => [
                ['start_time' => '19:00', 'end_time' => '13:00'],
            ],
        ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('admin.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'breaks.0.start_time' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testBreakReturnTimeCannotBeAfterEndTime()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者にログインして勤怠詳細ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.detail', $work->id));

        // 休憩終了時間が退勤時間より遅くなるよう設定して修正申請する
        $response = $this->from(route('admin.attendance.detail', $work->id))
        ->post(route('admin.attendance.request', $work->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remark' => 'テスト申請',
            'breaks' => [
                [
                    'start_time' => '12:00',
                    'end_time' => '19:00',
                ],
            ],
        ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('admin.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'breaks.0.end_time' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function testEmptyRemarkShowsErrorMessage()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        // ユーザーと勤怠データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 管理者にログインして勤怠詳細ページへ
        $response = $this->actingAs($admin, 'admin')->from(route('admin.attendance.detail', $work->id))
        ->post(route('admin.attendance.request', $work->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remark' => '',
            'breaks' => [
                ['start_time' => '12:00',
                'end_time' => '13:00',
                ],
            ],
        ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('admin.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください',
        ]);
    }
}
