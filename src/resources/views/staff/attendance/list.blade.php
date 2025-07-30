{{-- 勤怠一覧画面のbladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/attendance/list.css') }}">
@endsection

@section('content')
<div class="attendance-list-form">
    <ul class="list-title-form">
        <li class="list-title">勤怠一覧</li>
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

    {{-- 勤怠一覧を見る月を選択する行 --}}
    <div class="month-list-form">
        <a class="previous-month" href="{{ route('staff.attendance.list', ['month' => $prevMonth]) }}"><img class="left-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">前月</a>
        <div class="calender-title">
            <img class="calender-icon" src="{{ asset('storage/images/calender-logo.png') }}" alt="calender">
            <p class="selected-month">{{ $carbonMonth->format('Y/m') }}</p>
        </div>
        <a class="next-month" href="{{ route('staff.attendance.list', ['month' => $nextMonth]) }}">翌月<img class="right-arrow-icon" src="{{ asset('storage/images/arrow.png') }}" alt="calender">
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
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();
        @endphp

        @for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay())
        @php
        $attendance = $attendances->firstWhere('date', $date->toDateString());

        // 休憩時間合計
        $breakMinutes = $attendance && $attendance->breaks
        ? $attendance->breaks->sum(function ($break) {
        return \Carbon\Carbon::parse($break->start_time)->diffInMinutes(\Carbon\Carbon::parse($break->end_time));
        }) : 0;

        // 勤務時間合計
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

        {{-- 月末日を判定して月末日だけクラスメイをつける --}}
        <div class="work-date-list-form {{ $date->isSameDay($endOfMonth) ? 'last-day' : ''}}">
            {{-- 各勤怠データの表示 --}}
            @php
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek= $weekdays[$date->dayOfWeek];
            @endphp
            <div class="date">
                {{ $date->format('m/d')}} ({{ $dayOfWeek }})
            </div>
            <div class="work-start">{{ $attendance && $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</div>
            <div class="work-end">{{ $attendance && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</div>
            <div class="breaks">{{ $attendance ? $breakHours : '' }}</div>
            <div class="total">{{ $attendance ? $workHours : '' }}</div>
            <div class="detail">
                @if ($attendance)
                <a href="{{ route('staff.attendance.detail', ['work' => $attendance ? $attendance->id : '0', 'date' => $date->toDateString()]) }}" class="detail-link">詳細</a>
                @else
                <span class="no-detail">詳細</span>
                @endif
            </div>
        </div>
        @endfor
    </div>
</div>
@endsection