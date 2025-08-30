<?php

namespace Tests\Feature\staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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
    public function testRegisterFailsWhenPasswordComfirmationDoesNotMatch()
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
}
