<?php

namespace Tests\Feature\staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

/**
 * 【スタッフ用】登録・ログインのテスト
 */
class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    /**
     * 名前が未入力の場合のバリデーションテスト
     */
    public function testRegisterFailsWhenNameIsEmpty()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /**
     * メールアドレスが未入力の場合のバリデーションテスト
     */
    public function testRegisterFailsWhenEmailIsEmpty()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが7文字以下の場合のバリデーションテスト
     */
    public function testRegisterFailsWhenPasswordIsTooShort()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '123pass',
            'password_confirmation' => '123pass',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * パスワードと確認用パスワードが一致しない場合のバリデーションテスト
     */
    public function testRegisterFailsWhenPasswordConfirmationDoesNotMatch()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => '1234pass',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /**
     * パスワードが7文字以下の場合のバリデーションテスト
     */
    public function testRegisterFailsWhenPasswordIsEmpty()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 正常に会員登録できる場合のテスト
     */
    public function testUserCanCreate()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]); // 会員登録処理

        // 登録後に勤怠登録画面にリダイレクト
        $response->assertRedirect(route('staff.attendance.index'));

        // 登録したユーザーを取得
        $user = \App\Models\User::where('email', 'test@example.com')->first();

        // ログインされているか
        $this->assertAuthenticatedAs($user);
    }

    /**
     * メールアドレス未入力の場合のバリデーションテスト
     */
    public function testLoginFailsWhenEmailIsEmpty()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        $this->assertGuest(); // ログイン失敗時に誤って認証されないようチェック
    }

    /**
     * パスワード未入力の場合のバリデーションテスト
     */
    public function testLoginFailsWhenPasswordIsEmpty()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'testabc@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
        $this->assertGuest(); // ログイン失敗時に誤って認証されないようチェック
    }

    /**
     * 登録していないメールアドレスやパスワードでログインした場合のバリデーションテスト
     */
    public function testLoginFailsWithInvalidCredentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $invalidCases = [
            ['email' => 'testabc@example.com',
            'password' => 'password'], // メールアドレスが間違っている場合
            ['email' => 'test@example.com',
            'password' => 'password123'], // パスワードが間違っている場合
            ['email' => 'test123@example.com',
            'password' => 'password12345'], // どちらも間違っている場合
        ];

        foreach ($invalidCases as $case) {
            $response = $this->from('/login')->post('/login', $case);
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors([
                'email' => 'ログイン情報が登録されていません'
            ]);
            $this->assertGuest(); // ログイン失敗時に誤って認証されないようチェック
        }
    }
}
