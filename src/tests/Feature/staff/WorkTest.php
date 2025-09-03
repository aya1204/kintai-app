<?php

namespace Tests\Feature\staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Work;
use Whoops\Exception\Formatter;

/**
 * 日時取得・ステータス確認・出勤・休憩・退勤のテスト
 */
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
     * ステータス確認機能テスト
     */

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


    /**
     * 出勤機能テスト
     */

    /**
     * 出勤ボタンが正しく機能するテスト
     */
    public function testUserCanWorkStart()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤前は「出勤」ボタンが表示
        $response = $this->get(route('staff.attendance.index'));
        $response->assertSee('出勤');

        // 出勤処理を実行
        $response = $this->post(route('staff.attendance.workStart'));

        // リダイレクト先を確認
        $response->assertRedirect(route('staff.attendance.index'));

        // 出勤後は「出勤中」と表示される
        $response = $this->get(route('staff.attendance.index'));
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * 出勤は一日一回のみ
     */
    public function testUserCannotWorkStart()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 既に出勤済みレコードを作成
        $user->works()->create([
            'date' => now()->toDateString(),
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
        ]);

        $this->actingAs($user);

        // 出勤ボタンが表示されない
        $response = $this->get(route('staff.attendance.index'));
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function testWorkStartTimeIsShowInAttendanceList()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理を実行
        $this->post(route('staff.attendance.workStart'));

        // 勤怠一覧画面を確認
        $response = $this->get(route('staff.attendance.list', ['month' => now()->format('Y-m')]));

        // 出勤時刻が画面に表示されているか確認
        $response->assertSee(now()->format('H:i'));
    }


    /**
     * 休憩機能
     */

    /**
     * 休憩ボタンが正しく機能するテスト
     */
    public function testUserCanStartBreak()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤済みのデータを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // ステータス「出勤中」とボタン「休憩入」の確認
        $this->get(route('staff.attendance.index'))->assertSee('出勤中') // ステータスが「出勤中」と表示
            ->assertSee('休憩入'); // 「休憩入」ボタン表示

        // 休憩入り処理を実行して、リダイレクト後の画面で「休憩中」と表示される確認
        $this->followingRedirects()
            ->post(route('staff.attendance.takeBreak'))
            ->assertSee('休憩中');

        // DBに休憩開始が記録されていることを確認
        $this->assertDatabaseHas('breaks', [
            'work_id' => $work->id,
            'start_time' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩は一日に何回でもできるテスト
     */
    public function testUserCanTakeMultipleBreaks()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤済みのデータを作成
        Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s')
        ]);

        $this->actingAs($user);

        // ステータス「出勤中」とボタン「休憩入」の確認
        $this->get(route('staff.attendance.index'))->assertSee('出勤中') // ステータスが「出勤中」と表示
            ->assertSee('休憩入'); // 「休憩入」ボタン表示

        // 休憩入りの処理をして「休憩戻」ボタンが表示される
        $this->followingRedirects()
            ->post(route('staff.attendance.takeBreak'))
            ->assertSee('休憩戻');

        // 休憩戻りの処理をして再度「休憩入」ボタンが表示される
        $this->followingRedirects()
            ->post(route('staff.attendance.breakReturn'))
            ->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能するテスト
     */
    public function testUserCanEndBreak()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤済みのデータを作成
        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // ステータスが「出勤中」と表示されるか確認
        $this->get(route('staff.attendance.index'))->assertSee('出勤中');

        // 休憩入
        $this->post(route('staff.attendance.takeBreak'));

        // 休憩戻
        $this->followingRedirects()
            ->post(route('staff.attendance.breakReturn'))
            ->assertSee('出勤中');

        $this->assertDatabaseHas('breaks', [
            'work_id' => $work->id,
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->format('H:i:s'),
        ]);
    }

    /**
     * 休憩戻は一日に何回でもできるテスト
     */
    public function testUserCanReturnFromMultipleBreaks()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤済みのデータを作成
        Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // ステータスが「出勤中」と表示されるか確認
        $this->get(route('staff.attendance.index'))->assertSee('出勤中');

        // 休憩入りの処理をして「休憩戻」ボタンが表示される
        $this->followingRedirects()
            ->post(route('staff.attendance.takeBreak'))
            ->assertSee('休憩戻');

        // 休憩戻りの処理をして「休憩入」ボタンが表示される
        $this->followingRedirects()
            ->post(route('staff.attendance.breakReturn'))
            ->assertSee('休憩入');

            // 2回目の休憩入りの処理をして
        $this->followingRedirects()
            ->post(route('staff.attendance.takeBreak'))
            ->assertSee('休憩戻');

        $this->followingRedirects()
            ->post(route('staff.attendance.breakReturn'))
            ->assertSee('休憩入');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できるテスト
     */
    public function testBreakTimeIsShowInAttendanceList()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤済みのデータを作成
        Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        $startTime = now()->format('H:i');
        $endTime = now()->format('H:i');

        // 休憩入・戻処理を実行
        $this->post(route('staff.attendance.takeBreak'));
        $this->post(route('staff.attendance.breakReturn'));

        // 勤怠一覧画面を確認
        $response = $this->get(route('staff.attendance.list', ['month' => now()->format('Y-m')]));

        // 休憩時刻が画面に表示されているか確認
        $response->assertSee($startTime);
        $response->assertSee($endTime);
    }


    /**
     * 退勤機能
     */

    /**
     * 退勤ボタンが正しく機能するテスト
     * 退勤時刻が勤怠一覧画面で確認するテスト
     */
    public function testUserCanWorkEnd()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $work = Work::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // ステータスが「出勤中」と表示されるか確認
        $this->get(route('staff.attendance.index'))
        ->assertSee('出勤中');

        // 退勤前は「退勤」ボタンが表示
        $response = $this->get(route('staff.attendance.index'));
        $response->assertSee('退勤');

        // 退勤処理を実行
        $response = $this->followingRedirects()
            ->post(route('staff.attendance.workEnd'))
            ->assertSee('退勤済');

        // 勤怠一覧画面で退勤時刻を確認
        $response = $this->get(route('staff.attendance.list', [
            'month' =>now()->format('Y-m')
        ]));
        $response->assertSee(now()->format('H:i'));

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => $work->start_time,
            'end_time' => now()->format('H:i:s'),
        ]);
    }
}
