{{-- 【管理者用】勤怠詳細画面表示用Bladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/detail.css') }}">
@endsection

@section('content')

@if(session('success'))
<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="attendance-detail-form">
    <ul class="detail-title-form">
        <li class="detail-title">勤怠詳細</li>
    </ul>
    {{-- 勤怠修正申請フォーム(新規 or 更新で分岐) --}}
    <form class="correction-form" action="{{ $work ? route('admin.attendance.request', ['work' => $work->id]) : route('admin.attendance.create') }}" method="POST">
        @csrf
        <div class="detail-list-form">

            {{-- 名前表示 --}}
            <div class="name-form">
                <p class="name-title">名前</p>
                <p class="user-name">
                    {{ $user->name }}
                </p>
                <input class="user_id" type="hidden" value="{{ $user->id }}">
            </div>

            {{-- 日付表示 --}}
            <div class="date-form">
                <p class="date-title">日付</p>
                @php
                $workDate = $work ? \Carbon\Carbon::parse($work->date) : \Carbon\Carbon::parse($date);
                @endphp
                <p class="date-year">{{ $workDate->format('Y年') }}</p>
                <p class="date-month-day">{{ $workDate->format('n月j日') }}</p>
                <input type="hidden" name="date" value="{{ $workDate->format('Y-m-d') }}">
            </div>

            {{-- 出勤・退勤時間入力 --}}
            <div class="work-form">
                <p class="work-title">出勤・退勤</p>

                {{-- 出勤時間 --}}
                <div class="work-start-time-form">
                    <input class="work-start-time" type="text" name="start_time" value="{{ old('start_time', $work && $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i') : '')}}">
                    @error('start_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>

                <p class="wavy-line">〜</p>

                {{-- 退勤時間 --}}
                <div class="work-end-time-form">
                    <input class="work-end-time" type="text" name="end_time" value="{{ old('end_time', $work && $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i') : '')}}">
                    @error('end_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>


            {{-- 休憩時間入力 --}}
            @php
            $breakIndex = 1; // 何番目の休憩か
            $hasEmptyBreak = false; // 空の休憩が存在するか
            $breaks = $work ? $work->breaks : collect(); // 勤怠データが存在すれば登録済みの休憩時間を取得、なければ空のコレクションを代入
            @endphp

            @foreach ($breaks as $break)
            @php
            // 休憩開始・終了時刻があればH:i(例:13:15)の形式で表示
            // 空の休憩枠が1つでもあれば$hasEmptyBreakをtrueにする
            $start = $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null;
            $end = $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null;
            $hasEmptyBreak = $hasEmptyBreak|| (!$start && !$end);
            @endphp

            @if ($start || $end)
            <div class="break-form">
                <p class="break-title">{{ $breakIndex === 1 ? '休憩' : '休憩' . $breakIndex }}</p>

                {{-- 休憩開始 --}}
                <div class="take-break-time-form">
                    <input class="take-break-time" type="text" name="breaks[{{ $breakIndex }}][start_time]" value="{{ old('breaks.' . $breakIndex . '.start_time', $start) }}">
                    @error('breaks.' . $breakIndex . '.start_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>

                <p class="wavy-line">〜</p>

                {{-- 休憩終了 --}}
                <div class="break-return-time-form">
                    <input class="break-return-time" type="text" name="breaks[{{ $breakIndex }}][end_time]" value="{{ old('breaks.' . $breakIndex . '.end_time', $end) }}">
                    @error('breaks.' . $breakIndex . '.end_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @php
            $breakIndex++;
            @endphp
            @endif
            @endforeach

            {{-- 空の休憩枠が1つもなければ追加表示 --}}
            @if (!$hasEmptyBreak)
            <div class="break-form">
                <p class="break-title">休憩{{ $breakIndex}}</p>
                <div class="take-break-time-form">
                    <input class="take-break-time" type="text" name="breaks[{{ $breakIndex }}][start_time]" value="">
                    @error('breaks.' . $breakIndex . '.start_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
                <p class="wavy-line">〜</p>
                <div class="break-return-time-form">
                    <input class="break-return-time" type="text" name="breaks[{{ $breakIndex }}][end_time]" value="">
                    @error('breaks.' . $breakIndex . '.end_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @endif

            {{-- 備考欄 --}}
            <div class="remark-form">
                <p class="remark-title">備考</p>
                <div class="remark-input-form">
                    <textarea class="remark" name="remark">{{ old('remark', $work->remarks ?? '')}}</textarea>
                    @error('remark')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 修正ボタンエリア --}}
        <div class="correction-button-form">
            <button class="correction-button" type="submit">修正</button>
        </div>
    </form>
</div>
@endsection