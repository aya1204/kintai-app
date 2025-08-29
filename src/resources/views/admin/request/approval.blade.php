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
    <form class="approval-form" action="{{ route('admin.requests.approval', $request->id) }}" method="POST">
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

            {{-- 出勤・退勤 --}}
            @php
            $startTime = $requestWork && $requestWork->start_time
            ? \Carbon\Carbon::parse($requestWork->start_time)->format('H:i')
            : ($work && $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i') : '');
            $endTime = $requestWork && $requestWork->end_time
            ? \Carbon\Carbon::parse($requestWork->end_time)->format('H:i')
            : ($work && $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i') : '');
            @endphp
            <div class="work-form">
                <p class="work-title">出勤・退勤</p>

                {{-- 出勤時間 --}}
                <div class="work-start-time-form">
                    <p class="work-start-time">{{ $startTime ?: '-' }}</p>
                </div>

                <p class="wavy-line">〜</p>

                {{-- 退勤時間 --}}
                <div class="work-end-time-form">
                    <p class="work-end-time">{{ $endTime ?: '-' }}</p>
                </div>
            </div>

            {{-- 休憩時間入力 --}}
            @php
            $breaks = ($requestWork && $requestWork->requestBreaks->isNotEmpty())
            ? $requestWork->requestBreaks
            : ($work && $work->breaks ? $work->breaks : collect());
            // 勤怠データが存在すれば登録済みの休憩時間を取得、なければ空のコレクションを代入
            $breakIndex = 1; // 何番目の休憩か
            @endphp

            @foreach ($breaks as $break)
            @php
            // 休憩開始・終了時刻があればH:i(例:13:15)の形式で表示
            $start = $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null;
            $end = $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null; //
            @endphp

            <div class="break-form">
                <p class="break-title">{{ $breakIndex === 1 ? '休憩' : '休憩' . $breakIndex }}</p>

                {{-- 休憩開始 --}}
                <div class="take-break-time-form">
                    <p class="take-break-time">{{ $start }}</p>
                </div>

                <p class="wavy-line">〜</p>

                {{-- 休憩終了 --}}
                <div class="break-return-time-form">
                    <p class="break-return-time">{{ $end }}</p>
                </div>
            </div>
            @php
            $breakIndex++;
            @endphp
            @endforeach

            {{-- 空の休憩枠を1つ追加 --}}
            <div class="break-form">
                <p class="break-title">休憩{{ $breakIndex }}</p>
            </div>

            {{-- 備考欄 --}}
            @if($request->staff_remarks)
            <div class="remark-form">
                <label class="remark-title">備考</label>
                <div class="remark-text-form">
                    <p class="remark">{{ $request->staff_remarks }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- 承認ボタンエリア --}}
        <div class="approval-button-form">
            {{-- 承認済みは「承認済み」と表示し、ボタンを押せなくする --}}
            @if ($approved)
            <p class="approved">承認済み</p>
            @else
            <button class="approval-button" type="submit">承認</button>
            @endif
        </div>
    </form>
</div>
@endsection
