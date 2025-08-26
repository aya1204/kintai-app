<!-- メール認証誘導画面 -->
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="container">

    @if (session('status') === 'verification-link-sent')
    <div class="alert">認証メールを送信しました。メールをご確認ください。
    </div>
    @endif

    {{-- メール認証を送信するフォーム --}}
    <div class="messages">
        <p class="message">登録していただいたメールアドレスに認証メールを送付しました</p>
        <p class="message">メール認証を完了してください。</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="verify-btn">
            <button class="btn_email-verification">認証はこちらから</button>
        </div>
    </form>

    {{-- 再送信のためのフォーム --}}
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-link">認証メールを再送する</button>
    </form>
</div>
@endsection
