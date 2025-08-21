{{-- 【管理者用】修正申請承認画面表示用Bladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request/approval.css') }}">
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="attendance-detail-form">
    <ul class="detail-title-form">
        <li class="detail-title">勤怠詳細</li>
    </ul>
    {{-- 修正申請承認フォーム --}}
    <form class="approval-form" action="admin.fix.requests.approval" method="POST">
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
                $workDate = $work && $work->date ? $work->date : ($requestWork && $requestWork->date ? $requestWork->date : $date);
                $workDate = \Carbon\Carbon::parse($workDate);
                @endphp
                <p class="date-year">{{ $workDate->format('Y年') }}</p>
                <p class="date-month-day">{{ $workDate->format('n月j日') }}</p>
                <input type="hidden" name="date" value="{{ $workDate->format('Y-m-d') }}">
            </div>

            @php
            $startTime = '';
            $endTime = '';

            if ($work) {
            $startTime = $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i') : '';
            $endTime = $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i') : '';
            } elseif ($requestWork) {
            $startTime = $requestWork->start_time ? \Carbon\Carbon::parse($requestWork->start_time)->format('H:i') : '';
            $endTime = $requestWork->end_time ? \Carbon\Carbon::parse($requestWork->end_time)->format('H:i') : '';
            }
            @endphp

            {{-- 出勤・退勤 --}}
            @php
            $startTime = ($work && $work->start_time) ? $work->start_time : (($requestWork && $requestWork->start_time) ? $requestWork->start_time : null);
            $endTime = ($work && $work->end_time) ? $work->end_time : (($requestWork && $requestWork->end_time) ? $requestWork->end_time : null);
            $startTime = $startTime ? \Carbon\Carbon::parse($startTime)->format('H:i') : '';
            $endTime = $endTime ? \Carbon\Carbon::parse($endTime)->format('H:i') : '';
            @endphp
            <div class="work-form">
                <p class="work-title">出勤・退勤</p>

                {{-- 出勤時間 --}}
                <div class="work-start-time-form">
                    <input class="work-start-time" type="text" name="start_time" value="{{ old('start_time', $startTime) }}">
                    @error('start_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>

                <p class="wavy-line">〜</p>

                {{-- 退勤時間 --}}
                <div class="work-end-time-form">
                    <input class="work-end-time" type="text" name="end_time" value="{{ old('end_time', $endTime) }}">
                    @error('end_time')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>


            {{-- 休憩時間入力 --}}
            @php
            $breaks = ($work && $work->breaks) ? $work->breaks: (($requestWork && $requestWork->requestBreaks) ? $requestWork->requestBreaks : collect());
            $breakIndex = 1; // 何番目の休憩か
            $breaks = ($work && $work->breaks) ? $work->breaks : (($requestWork && $requestWork->requestBreaks) ? $requestWork->requestBreaks : collect()); // 勤怠データが存在すれば登録済みの休憩時間を取得、なければ空のコレクションを代入
            @endphp

            @foreach ($breaks as $break)
            @php
            // 休憩開始・終了時刻があればH:i(例:13:15)の形式で表示
            $start = $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null;
            $end = $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null;
            @endphp

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
            @endforeach

            {{-- 空の休憩枠を1つ追加 --}}
            <div class="break-form">
                <p class="break-title">休憩{{ $breakIndex }}</p>
                <div class="take-break-time-form">
                    <input class="take-break-time" type="text" name="breaks[{{ $breakIndex }}][start_time]" value="">
                </div>
                <p class="wavy-line">〜</p>
                <div class="break-return-time-form">
                    <input class="break-return-time" type="text" name="breaks[{{ $breakIndex }}][end_time]" value="">
                </div>
            </div>

            {{-- 備考欄 --}}
            @php
            $remark = ($work && $work->remarks) ? $work->remarks : (($requestWork && $requestWork->remark) ? $requestWork->remark : '');
            @endphp
            <div class="remark-form">
                <p class="remark-title">備考</p>
                <div class="remark-input-form">
                    <textarea class="remark" name="remark">{{ old('remark', $remark)}}</textarea>
                    @error('remark')
                    <div class="error-messages">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 承認ボタンエリア --}}
        <div class="approval-button-form">
            {{-- 承認済みは「承認済み」と表示し、ボタンを押せなくする --}}
            @if ($approved)
            <p class="text-danger">承認済み</p>
            @else
            <button class="approval-button" type="submit">承認</button>
            @endif
        </div>
    </form>
</div>
@endsection