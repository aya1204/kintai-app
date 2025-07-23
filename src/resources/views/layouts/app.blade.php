<!-- ヘッダー部分のbladeファイル -->
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
            <!-- ロゴ -->
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

            <!-- ナビゲーション -->
            <div class="header__nav">
                <a href="{{ route('staff.attendance.work') }}">勤怠</a>
                <a href="{{ route('staff.attendance.staff_list') }}">勤怠一覧</a>
                <a href="{{ route('staff.request.staff_request') }}">申請</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </div>
            @endunless
        </div>
    </header>

    <main>
        @yield('content')
        @yield('js')
    </main>
</body>

</html>