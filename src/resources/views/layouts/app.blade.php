{{-- ヘッダー部分のbladeファイル --}}
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header {{ Route::is('login') || Route::is('register') || Route::is('verification.notice') ? 'header--centered' : ''}}">
        <div class="header__container">
            {{-- ロゴ --}}
            <div class="header__logo">
                <a href="/">
                    <img src="{{ asset('storage/images/logo.svg') }}" alt="COACHTECH">
                </a>
            </div>

            @php
            // 表示を制限したいルート一覧
            $hideHeaderElements = in_array(Route::currentRouteName(),[
            'login', // ログイン画面
            'register', // 新規登録画面
            'verification.notice', // メール認証画面
            ]);
            @endphp

            @unless ($hideHeaderElements)

            {{-- 管理者ログイン中ナビゲーション --}}
            @if(request()->is('admin/*') && Auth::guard('admin')->check())
            <div class="header__nav">
                <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                <a href="{{ route('admin.request.list') }}">申請一覧</a>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </div>

            {{-- スタッフログイン中ナビゲーション --}}
            @elseif (isset($status) && $status === 'after_work')
            {{-- 退勤済みのときだけのヘッダー --}}
            <div class="header__nav">
                <a href="{{ route('staff.attendance.list') }}">今月の勤怠一覧</a>
                <a href="{{ route('staff.request.list') }}">申請一覧</a>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </div>
            @else
            {{-- 通常のヘッダー --}}
            <div class="header__nav">
                <a href="{{ route('staff.attendance.index') }}">勤怠</a>
                <a href="{{ route('staff.attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('staff.request.list') }}">申請</a>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </div>
            @endif
            @endunless
        </div>
    </header>

    <main>
        @yield('content')
        @yield('js')
    </main>
</body>

</html>