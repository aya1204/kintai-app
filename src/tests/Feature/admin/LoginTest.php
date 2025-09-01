<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Manager;

class LoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合のバリデーションテスト
     */
    public function testLoginFailsWhenEmailIsEmpty()
    {
        // 管理者ユーザーをDBに作成
        $admin = Manager::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // 直前に管理者ログイン画面へアクセスする
        $this->get('/admin/login');

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        // ログイン失敗時 → 元のログイン画面へリダイレクト
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest('admin'); // ログイン失敗時に誤って認証されないようチェック
    }

    /**
     * パスワードが未入力の場合のバリデーションテスト
     */
    public function testLoginFailsWhenPasswordIsEmpty()
    {
        // 管理者ユーザーをDBに作成
        $admin = Manager::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // 直前に管理者ログイン画面へアクセスする
        $this->get('/admin/login');

        $response = $this->post('/admin/login', [
            'email' => 'manager@example.com',
            'password' => '',
        ]);

        // ログイン失敗時 → 元のログイン画面へリダイレクト
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest('admin'); // ログイン失敗時に誤って認証されないようチェック
    }

    /**
     * 登録していないメールアドレスやパスワードでログインした場合のバリデーションテスト
     */
    public function testLoginFailsWithInvalidCredentials()
    {
        // 管理者ユーザーをDBに作成
        $admin = Manager::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // 直前に管理者ログイン画面へアクセスする
        $this->get('/admin/login');

        $invalidCases = [
            ['email' => 'manager123@example.com',
            'password' => 'password'], // メールアドレスが間違っている場合
            ['email' => 'manager@example.com',
            'password' => 'password123'], // パスワードが間違っている場合
            ['email' => 'manager123@example.com',
            'password' => 'password123'], // どちらも間違っている場合
        ];

        foreach ($invalidCases as $case) {
            $response = $this->from('/admin/login')->post('/admin/login', $case);
            $response->assertRedirect('/admin/login');
            $response->assertSessionHasErrors([
                'email' => 'ログイン情報が登録されていません'
            ]);
            $this->assertGuest('admin'); // ログイン失敗時に誤って認証されないようチェック
        }
    }
}
