<!-- 新規登録画面用のbladeファイル -->
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="register-form__content">
    <div class="register-form__heading">
        <h2 class="register-form__heading-title">会員登録</h2>
    </div>
    <form class="form" action="{{ route('register') }}" method="post">
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">ユーザー名</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="text" class="registration-items" name="name" value="{{ old('name') }}" />
                </div>
                <div class="form__error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">メールアドレス</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="email" class="registration-items" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form__error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" class="registration-items" name="password" />
                </div>
                <div class="form__error">
                    @error('password')
                    <!-- 「パスワードと一致しません」以外（必須、8文字以上）ここに表示 -->
                    @if ($message !== 'パスワードと一致しません')
                    {{ $message }}
                    @endif
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">確認用パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" class="registration-items" name="password_confirmation" />
                </div>
                <div class="form__error">
                    @error('password')
                    <!-- 「パスワードと一致しません」エラーだけここに表示 -->
                    @if ($message === 'パスワードと一致しません')
                    {{ $message }}
                    @endif
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">登録する</button>
        </div>
    </form>
    <div class="login__link">
        <a href="/login" class="login__button-submit">ログインはこちら</a>
    </div>
</div>
@endsection