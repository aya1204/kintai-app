{{-- スタッフ別勤怠一覧画面を表示するBlade --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/user/staff_attendance_list.css') }}">
@endsection

@section('content')

@if(session('success'))
<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="attendance-list-form">
    <ul class="list-title-form">
        <li class="list-title">{{ $user->name }}さんの勤怠</li>
    </ul>
    {{-- 月の切り替え処理 --}}
    @php
    // 現在表示している月
    $carbonMonth = \Carbon\Carbon::createFromFormat('Y-m', $currentMonth);
    // 現在表示している月の前月
    $prevMonth = $carbonMonth->copy()->subMonth()->format('Y-m');
    // 現在表示している月の翌月
    $nextMonth = $carbonMonth->copy()->addMonth()->format('Y-m');
    @endphp

    {{-- 勤怠一覧：月の切り替え(前月・当月・翌月) --}}
    <div class="month-list-form">
        <a class="previous-month" href="{{ route('admin.staff.attendance.list', ['user' => $user->id, 'month' => $prevMonth]) }}"><img class="left-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">前月</a>
        <div class="calender-title">
            <img class="calender-icon" src="{{ asset('storage/images/calender-logo.png') }}" alt="calender">
            <p class="selected-month">{{ $carbonMonth->format('Y/m') }}</p>
        </div>
        <a class="next-month" href="{{ route('admin.staff.attendance.list', ['user' => $user->id, 'month' => $nextMonth]) }}">翌月<img class="right-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">
        </a>
    </div>
    <div class="attendance-date-list-form">
        <div class="attendance-date-list-title-form">
            <p class="date-title">日付</p>
            <p class="work-start-title">出勤</p>
            <p class="work-end-title">退勤</p>
            <p class="breaks-title">休憩</p>
            <p class="total-title">合計</p>
            <p class="detail-title">詳細</p>
        </div>
        {{-- 勤怠データの表示 --}}
        @php
        // 月の最初の日と最後の日
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();
        @endphp

        {{-- 各日付の勤怠情報を1日ずつループで表示 --}}
        @for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay())
        @php
        // 表示中の日付の勤怠データを取得
        $attendance = $attendances->firstWhere('date', $date->toDateString());

        // 休憩時間の合計
        $breakMinutes = $attendance && $attendance->breaks
        ? $attendance->breaks->sum(function ($break) {
        return \Carbon\Carbon::parse($break->start_time)->diffInMinutes(\Carbon\Carbon::parse($break->end_time));
        }) : 0;

        // 勤務時間合計(休憩時間を引いた時間、分単位で)
        $workMinutes = ($attendance && $attendance->start_time && $attendance->end_time)
        ? \Carbon\Carbon::parse($attendance->start_time)->diffInMinutes(\Carbon\Carbon::parse($attendance->end_time)) - $breakMinutes
        : 0;

        // 勤務時間・休憩時間をhh:mm形式で表示
        $workHours = $workMinutes > 0
        ? floor($workMinutes / 60) . ':' . str_pad($workMinutes % 60, 2, '0', STR_PAD_LEFT)
        : '';

        $breakHours = $breakMinutes > 0
        ? floor($breakMinutes / 60) . ':' . str_pad($breakMinutes % 60, 2, '0', STR_PAD_LEFT)
        : '';
        @endphp

        {{-- 月末日を判定して月末日だけクラス名をつける --}}
        <div class="work-date-list-form {{ $date->isSameDay($endOfMonth) ? 'last-day' : ''}}">
            {{-- 各勤怠データの表示 --}}
            @php
            // 曜日を日本語で取得
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek= $weekdays[$date->dayOfWeek];
            @endphp

            {{-- 日付と曜日 --}}
            <div class="date">
                {{ $date->format('m/d')}} ({{ $dayOfWeek }})
            </div>

            {{-- 出勤時間 --}}
            <div class="work-start">{{ $attendance && $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</div>

            {{-- 退勤時間 --}}
            <div class="work-end">{{ $attendance && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</div>

            {{-- 休憩時間合計 --}}
            <div class="breaks">{{ $attendance ? $breakHours : '' }}</div>

            {{-- 勤務時間合計 --}}
            <div class="total">{{ $attendance ? $workHours : '' }}</div>

            {{-- 詳細ページへのリンク --}}
            <div class="detail">
                @if ($attendance) {{-- その日の勤怠がある場合 --}}
                <a class="detail-link" href="{{ route('admin.attendance.detail', ['work' => $attendance->id]) }}">詳細</a>
                @else {{-- 勤怠がない場合は新規作成 --}}
                <a class="detail-link" href="{{ route('admin.attendance.createForm', ['user' => $user->id, 'date' => $date->toDateString()]) }}">詳細</a>
                @endif
            </div>
        </div>
        @endfor
    </div>
    <div class="printing-form">
        <form action="{{ route('admin.staff.attendance.csv.export', ['user' => $user->id] )}}" method="POST">
            @csrf
            <input type="hidden" name="month" value="{{ $currentMonth}}">
            <button class="printing-button" type="submit">CSV出力</button>
        </form>
    </div>
</div>
@endsection