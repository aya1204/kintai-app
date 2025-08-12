{{-- 【管理者用】勤怠一覧画面のBladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/list.css') }}">
@endsection

@section('content')

@php
// 表示日（デフォルトは今日）
$carbonDate = \Carbon\Carbon::parse($currentDate);
@endphp

@if(session('success'))
<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="attendance-list-form">
    <ul class="list-title-form">
        <li class="list-title">{{ $carbonDate->format('Y年n月j日')}}の勤怠</li>
    </ul>
    {{-- 月の切り替え処理 --}}
    @php
    // 現在表示している日の前日
    $prevDate = $carbonDate->copy()->subDay()->format('Y-m-d');
    // 現在表示している日の翌日
    $nextDate = $carbonDate->copy()->addDay()->format('Y-m-d');
    @endphp

    {{-- 勤怠一覧：日付の切り替え(前日・当日・翌日) --}}
    <div class="day-list-form">
        <a class="previous-day" href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}"><img class="left-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">前日</a>
        <div class="calender-title">
            <img class="calender-icon" src="{{ asset('storage/images/calender-logo.png') }}" alt="calender">
            <p class="selected-day">{{ $carbonDate->format('Y/m/d') }}</p>
        </div>
        <a class="next-day" href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">翌日<img class="right-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">
        </a>
    </div>
    <div class="attendance-date-list-form">
        <div class="attendance-date-list-title-form">
            <p class="name-title">名前</p>
            <p class="work-start-title">出勤</p>
            <p class="work-end-title">退勤</p>
            <p class="breaks-title">休憩</p>
            <p class="total-title">合計</p>
            <p class="detail-title">詳細</p>
        </div>
        {{-- スタッフごとの勤怠データの表示 --}}
        @foreach($attendances as $attendance)
        @php
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
        <div class="attendance-row">

            {{-- 日付と曜日 --}}
            <div class="user-name">
                {{ $attendance->user->name}}
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
                <a href="{{ route('admin.attendance.detail', ['work' => $attendance->id, 'user' => $attendance->user_id, 'date' => $attendance->date]) }}" class="detail-link">詳細</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection