{{-- ログイン画面用のbladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/auth/staff_login.css') }}">
@endsection

@section('content')

<div class="login-form__content">
    <div class="login-form__heading">
        <h2>ログイン</h2>
    </div>

    <form class="form" action="/login" method="post">
        @csrf

        {{-- メールアドレス --}}
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">メールアドレス</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="email" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form__error">
                    {{-- 上部で表示した「ログイン情報〜」はここには表示しない --}}
                    @if ($errors->has('email') && $errors->first('email') !== 'ログイン情報が登録されていません')
                    {{ $errors->first('email') }}
                    @endif
                </div>
            </div>
        </div>

        {{-- パスワード --}}
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" name="password">
                </div>
                <div class="form__error">
                    @error('password')
                    {{ $message }}
                    @enderror

                    @if ($errors->has('email') && $errors->first('email') === 'ログイン情報が登録されていません')
                    {{ $errors->first('email') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">ログインする</button>
        </div>
    </form>
    <div class="register__link">
        <a href="/register" class="register__button-submit">会員登録はこちら</a>
    </div>
</div>
@endsection
