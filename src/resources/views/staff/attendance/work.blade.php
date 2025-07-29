{{-- 出勤・退勤・休憩時間登録のbladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/attendance/work.css') }}">
@endsection

@section('content')
<div class="attendance-form">
    <div class="work-status-form">
        <span class="work-status">
            @if ($status === 'before_work')
            勤務外
            @elseif ($status === 'working')
            出勤中
            @elseif ($status === 'on_break')
            休憩中
            @elseif ($status === 'after_work')
            退勤済
            @endif
        </span>
    </div>

    <div class="date-time">
        <p class="date">{{ date('Y年m月d日') }}</p>
        <p class="time" id="real-time-clock"></p>
    </div>

    <div class="attendance-button-form">
        @if ($status === 'before_work')
        <form method="POST" action="{{ route('staff.attendance.workStart') }}">
            @csrf
            <button type="submit" class="work-start-button">出勤</button>
        </form>
        @elseif ($status === 'working')
        <form method="POST" action="{{ route('staff.attendance.workEnd') }}">
            @csrf
            <button type="submit" class="work-end-button">退勤</button>
        </form>
        <form method="POST" action="{{ route('staff.attendance.takeBreak') }}">
            @csrf
            <button type="submit" class="take-break-button">休憩入</button>
        </form>
        @elseif ( $status === 'on_break')
        <form method="POST" action="{{ route('staff.attendance.breakReturn') }}">
            @csrf
            <button type="submit" class="break-return-button">休憩戻</button>
        </form>
        @elseif ( $status === 'after_work')
        <p class="good-job">お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    function updateClock() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const currentTime = `${hours}:${minutes}`;
        document.getElementById('real-time-clock').textContent = currentTime;
    }

    // 初回即時実行
    updateClock();
    // 毎秒更新
    setInterval(updateClock, 1000);
</script>
@endsection
