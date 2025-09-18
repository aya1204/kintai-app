<?php

namespace Tests\Feature\staff;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\VerificationExpectation;
use PhpParser\Node\Expr\FuncCall;
use PHPUnit\Framework\Error\Notice;
use Tests\TestCase;

/**
 * メール認証機能のテスト
 */
class EmailVerificationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase, WithFaker;

    /**
     * 会員登録後、認証メールが送信される
     */
    public function testUserReceivesVerificationEmailAfterRegistration()
    {
        // ①通知をフェイクにする（実際には送信せず、送信されたかどうかだけ検証）
        Notification::fake();

        // ② /register にPOSTして会員登録処理を実行
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // ③ DBに保存された最初にユーザーを取得
        $user = User::first();

        // ④そのユーザーに VerifyEmail 通知が送信されたことを確認
        Notification::assertSentTo($user, VerifyEmail::class);

        // ⑤ 登録後のリダイレクト先が「メール認証誘導画面」になっているか確認
        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function testUserCanAccessEmailVerificationLinkFromEmailAuthenticationGuidanceScreen()
    {
        // ユーザーを作成してログイン状態にする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' =>null, // 未認証ユーザー
        ]);

        // １．メール認証誘導画面を表示する
        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertStatus(200);

        // ２．誘導画面にMailHogのリンクが含まれているか確認
        $response->assertSee('http://localhost:8025');

        // ３．擬似的にボタン押下→ MailHogにアクセス
        $mailhogUrl = 'http://localhost:8025';

        // リンクが'http://localhost:8025'であることを確認
        $this->assertEquals($mailhogUrl, 'http://localhost:8025');
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function testUserIsRedirectedToAttendanceRegistrationPageAfterEmailVerification()
    {
        // メールが送信されたことをテストできるようにする
        Notification::fake();

        // 未認証ユーザーをDBに作成
        $user = User::factory()->unverified()->create();

        // 未認証ユーザーに対してメール認証通知を送る
        $user->sendEmailVerificationNotification();

        // 認証を誘導するメールを確認
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user) {
            $mailData = $notification->toMail($user);
            $url = $mailData->actionUrl;

            // メールに届いたリンクにアクセス（認証完了）
            $response = $this->actingAs($user)->get($url);

            // 部分一致で勤怠登録画面に遷移するか確認
            $this->assertStringContainsString('/attendance', $response->headers->get('Location'));

            // 認証状態を最新にして「認証済み」になったか確認する
            $this->assertTrue($user->fresh()->hasVerifiedEmail());

            return true;
        });
    }
}
