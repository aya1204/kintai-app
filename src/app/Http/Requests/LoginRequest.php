<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $route = $this->route()->getName(); // ルート名で分岐する

        // もしadmin.がつくルートなら
        if (str_starts_with($route, 'admin.')) {
            return [
                'email' => ['required', 'email', 'exists:managers,email'], // 管理者のメールアドレス
                'password' => ['required'],
            ];
        }

        // スタッフログイン用バリデーション
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '',
            'password.required' => 'パスワードを入力してください',
            'password.min' => '',
        ];
    }
}
