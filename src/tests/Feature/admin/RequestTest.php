<?php

namespace Tests\Feature\admin;

use App\Models\Manager;
use App\Models\User;
use App\Models\Work;
use App\Models\BreakTime;
use App\Models\Request as WorkRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 【管理者用】勤怠情報修正機能テスト
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
     * 承認待ちの修正申請が全て表示されている
     * 管理ユーザーにログイン→修正申請一覧ページを開く→承認待ちのタブを開く
     */
    public function testDisplaysAllPendingRequests()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 勤怠データを3日分作成
        $dates = [
            now()->subDay()->format('Y-m-d'),
            now()->toDateString(),
            now()->addDay()->format('Y-m-d'),
        ];

        $works = collect($dates)->map(function ($date) use ($user) {
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

            return $work;
        });

        // ループして申請を作成
        $remarks = ['テスト申請①', 'テスト申請②', 'テスト申請③'];

        foreach ($remarks as $i => $remark) {
            $work = $works[$i]; // 各日付の勤怠に対して申請
            $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
                'start_time' => '08:00',
                'end_time' => '17:00',
                'remark' => $remark,
                'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
            ]);

            $this->assertDatabaseHas('requests', [
                'work_id' => $work->id,
                'staff_remarks' => $remark,
            ]);
        }

        // 管理者ログイン後、承認待ちタブで申請時の備考が表示されるか確認
        $response = $this->actingAs($admin, 'admin')->get(route('admin.request.list', ['tab' => 'wait']));
        $response->assertStatus(200);

        foreach ($remarks as $remark) {
            $response->assertSee($remark);
        }
    }

    /**
     * 承認済みの修正申請が全て表示されている
     * 管理ユーザーにログイン→修正申請一覧ページを開く→承認済みのタブを開く
     */
    public function testDisplaysAllApprovedRequests()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create(['id' => 1]);

        // 勤怠データを作成
        $worksData = [
            ['date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '18:00', 'remark' => 'テスト申請①'],
            ['date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '18:00', 'remark' => 'テスト申請②']
        ];

        // 修正申請をする
        $requests = collect($worksData)->map(function ($data) use ($user) {
            $work = Work::factory()->create([
                'user_id' => $user->id,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);

            $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
                'start_time' => '08:00',
                'end_time' => '17:00',
                'remark' => $data['remark'],
                'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
            ]);

            return WorkRequest::where('work_id', $work->id)->first();
        });

        // 管理者にログインして修正申請を承認する
        $this->actingAs($admin, 'admin');

        $requests->each(function ($request) {
            $this->post(route('admin.requests.approval', $request->id), ['approved' => true]);
            $this->assertDatabaseHas('requests', ['id' => $request->id, 'approved' => true]);
        });

        // 管理者にログインして修正申請一覧ページの承認済みタブへ移動
        $response = $this->actingAs($admin, 'admin')->get(route('admin.request.list', ['tab' => 'clear']));
        $response->assertStatus(200);

        // スタッフが修正申請時に書いた備考が表示されているか確認
        foreach ($worksData as $data) {
            $response->assertSee($data['remark']);
        }
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     * 管理ユーザーにログイン→修正申請一覧ページを開く→修正申請一覧ページの詳細リンクを押下→修正申請詳細ページを開く
     */
    public function testCorrectlyDisplayDetailsOfAmendmentRequests()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create(['id' => 1]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 勤怠データを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        // 休憩入りと休憩戻りデータを作成
        $breaks = BreakTime::factory()->create([
            'work_id' => $work->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        // 修正申請を作成
        $response = $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
            'start_time' => '08:00',
            'end_time' => '17:00',
            'remark' => 'テスト申請',
            'manager_id' => $admin->id,
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', '修正申請が送信されました');

        // データベースに申請を保存
        $request = WorkRequest::where('work_id', $work->id)->first();
        $this->assertDatabaseHas('requests', [
            'work_id' => $work->id,
            'staff_remarks' => 'テスト申請',
        ]);

        // 管理者ログイン後、修正申請一覧ページの詳細ボタンを押して修正申請詳細ページへ
        $response = $this->actingAs($admin, 'admin')->get(route('admin.fix.requests.approval', $request->id));
        $response->assertStatus(200);

        // 修正申請された勤怠情報と承認ボタンが表示されるか確認
        $response->assertSee('08:00');
        $response->assertSee('17:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('承認');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     * 管理ユーザーにログイン→修正申請一覧ページを開く→修正申請一覧ページの詳細リンクを押下→修正申請詳細ページを開く→承認ボタンを押す
     */
    public function testAdminCanApproveCorrectionRequest()
    {
        /** @var \App\Models\Manager $admin */
        $admin = Manager::factory()->create(['id' => 1]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $postResponse = $this->actingAs($user)->post(route('staff.attendance.request', $work->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'remark' => 'テスト申請',
            'breaks' => [['start_time' => '12:00', 'end_time' => '13:00']],
        ]);

        $request = WorkRequest::where('work_id', $work->id)->first();

        $this->actingAs($admin, 'admin');$this->assertAuthenticatedAs($admin, 'admin');
        $response = $this->followingRedirects()
        ->actingAs($admin, 'admin')
        ->post(route('admin.requests.approval', $request->id), ['approved' => true]);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id, 'approved' => true
        ]);

        $response->assertSee('承認済み');
    }
}
