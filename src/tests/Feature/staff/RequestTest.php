<?php

namespace Tests\Feature\staff;

use App\Models\User;
use App\Models\Manager;
use App\Models\Work;
use App\Models\BreakTime;
use App\Models\Request as WorkRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 【スタッフ用】勤怠詳細情報修正機能のテスト
 */
class RequestTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合のバリデーションテスト
     */
    public function testWorkStartTimeCannotBeAfterEndTime()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $this->actingAs($user);

        // 勤怠詳細画面にアクセス
        $response = $this->get(route('staff.attendance.detail', $work->id));
        $response->assertStatus(200);

        $response = $this->from(route('staff.attendance.detail', $work->id))
        ->post(route('staff.attendance.request', $work->id),[
            'start_time' => '18:00',
            'end_time' => '09:00',
            'remark' => 'テスト申請',
        ]);

        $response->assertRedirect(route('staff.attendance.detail', $work->id));

        $this->followingRedirects()
        ->get(route('staff.attendance.detail', $work->id))
        ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合のバリデーションテスト
     */
    public function testBreakStartTimeCannotBeAfterEndTime()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $this->actingAs($user);

        // バリデーションエラーになる休憩データを送信
        $response = $this->from(route('staff.attendance.detail', $work->id))
            ->post(route('staff.attendance.request', $work->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remark' => 'テスト申請',
                'breaks' => [
                    ['start_time' => '19:00', 'end_time' => '13:00'],
                ],
            ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('staff.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'breaks.0.start_time' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合のバリデーションテスト
     */
    public function testBreakReturnTimeCannotBeAfterEndTime()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $this->actingAs($user);

        // バリデーションエラーになる休憩データを送信
        $response = $this->from(route('staff.attendance.detail', $work->id))
            ->post(route('staff.attendance.request', $work->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remark' => 'テスト申請',
                'breaks' => [
                    ['start_time' => '12:00', 'end_time' => '19:00'],
                ],
            ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('staff.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'breaks.0.end_time' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考欄が未入力の場合のバリデーションテスト
     */
    public function testRemarksColumnIsBlank()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $this->actingAs($user);

        // バリデーションエラーになる休憩データを送信
        $response = $this->from(route('staff.attendance.detail', $work->id))
            ->post(route('staff.attendance.request', $work->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remark' => '',
                'breaks' => [
                    ['start_time' => '12:00', 'end_time' => '13:00'],
                ],
            ]);

        // バリデーションでリダイレクト
        $response->assertRedirect(route('staff.attendance.detail', $work->id));

        // エラーメッセージを確認
        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください',
        ]);
    }

    /**
     * 修正申請を実行できるか
     */
    public function testSubmitACorrectionRequest()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $this->actingAs($user);

        // 休憩データを送信して修正申請を実行する
        $this->actingAs($user)
        ->post(route('staff.attendance.request', $work->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remark' => 'テスト申請',
                'breaks' => [
                    ['start_time' => '12:00', 'end_time' => '13:00'],
                ],
            ])->assertSessionHas('success', '修正申請が送信されました');
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されているか
     */
    public function testDisplaysAllPendingRequests()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データ①を作成
        $work1 = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 出勤データの修正と休憩データを入れて修正申請をする①
        $this->actingAs($user)->post(route('staff.attendance.request', $work1->id), [
            'start_time' => '08:00',
            'end_time' => '17:00',
            'remark' => 'テスト申請①',
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        // 出勤・退勤データ②を作成
        $work2 = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '19:00',
        ]);

        // 出勤データの修正と休憩データを入れて修正申請をする②
        $this->actingAs($user)->post(route('staff.attendance.request', $work2->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remark' => 'テスト申請②',
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        // 申請①をデータベースに保存する
        $this->assertDatabaseHas('requests', [
            'work_id' => $work1->id,
            'staff_remarks' => 'テスト申請①',
        ]);

        // 申請②をデータベースに保存する
        $this->assertDatabaseHas('requests', [
            'work_id' => $work2->id,
            'staff_remarks' => 'テスト申請②',
        ]);

        // 「承認待ち」のタブで申請済みのデータが全て表示されるか
        $response = $this->get(route('staff.request.list', ['tab' => 'wait']));
        $response->assertStatus(200);
        $response->assertSee('テスト申請①');
        $response->assertSee('テスト申請②');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されているか
     */
    public function testDisplaysAllApprovedRequests()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create(['id' => 1]);

        // 出勤・退勤データを複数作成
        $works = [
            ['date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '18:00', 'remark' => 'テスト申請①'],
            ['date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '19:00', 'remark' => 'テスト申請②'],
        ];

        $requests = [];
        foreach ($works as $workData) {
            $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => $workData['date'],
            'start_time' => $workData['start_time'],
            'end_time' => $workData['end_time'],
        ]);

        // 出勤データの修正と休憩データを入れて修正申請をする
        $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
            'start_time' => '08:00',
            'end_time' => '17:00',
            'remark' => $workData['remark'],
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        $requests[] = WorkRequest::where('work_id', $work->id)->first();
        }

        $this->actingAs($admin, 'admin');
        foreach ($requests as $request) {
            $this->post(route('admin.requests.approval', $request->id), [
            'approved' => true
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'approved' => true,
        ]);
        }

        $this->actingAs($user);
        // 「承認待ち」のタブで申請済みのデータが全て表示されるか
        $response = $this->get(route('staff.request.list', ['tab' => 'clear']));
        $response->assertStatus(200);
        $response->assertSee('テスト申請①');
        $response->assertSee('テスト申請②');
    }

    /**
     * 各申請の「詳細」を押すと勤怠詳細画面に遷移するか
     */
    public function testRedirectsToAttendanceDetailWhenClickingDetailLink()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 修正申請を作成
        $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
            'start_time' => '08:00',
            'end_time' => '17:00',
            'remark' => 'テスト申請',
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        // 一覧画面を開いて詳細リンクを探す
        $response = $this->actingAs($user)->get(route('staff.request.list'));
        $response->assertStatus(200);

        // 詳細リンクが含まれていることを確認
        $response = $this->actingAs($user)->get(route('staff.attendance.detail', $work->id));

        // 詳細リンクへアクセスすると勤怠詳細画面が表示されるか確認
        $detailResponse = $this->get(route('staff.attendance.detail', $work->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee((string) $work->date);
    }
}
