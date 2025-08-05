{{-- 勤怠詳細画面表示用Bladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/attendance/detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-form">
    <ul class="detail-title-form">
        <li class="detail-title">勤怠詳細</li>
    </ul>
    {{-- <form class="correction-form" action="{{ route('staff.attendance.request', ['work' => $work->id]) }}" method="POST"> --}}
    <form class="correction-form" action="{{ $work ? route('staff.attendance.request', ['work' => $work->id]) : route('staff.attendance.create') }}" method="POST">
        @csrf
        <div class="detail-list-form">

            {{-- 名前 --}}
            <div class="name-form">
                <p class="name-title">名前</p>
                <p class="user-name">
                    {{ $user->name }}
                </p>
                {{-- <input class="user_id" type="hidden" value="{{ $work->user ? $work->user->id : '' }}"> --}}
                <input class="user_id" type="hidden" value="{{ $user->id }}">
            </div>

            {{-- 日付 --}}
            <div class="date-form">
                <p class="date-title">日付</p>
                @php
                $workDate = $work ? \Carbon\Carbon::parse($work->date) : \Carbon\Carbon::parse($date);
                @endphp
                <p class="date-year">{{ $workDate->format('Y年') }}</p>
                <p class="date-month-day">{{ $workDate->format('m月d日') }}</p>
                <input type="hidden" name="date" value="{{ $workDate->format('Y-m-d') }}">
            </div>

            {{-- 出勤・退勤 --}}
            <div class="work-form">
                <p class="work-title">出勤・退勤</p>
                <input class="work-start-time" type="text" name="start_time" value="{{ old('start_time', $work && $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i') : '')}}">
                <p class="wavy-line">〜</p>
                <input class="work-end-time" type="text" name="end_time" value="{{ old('end_time', $work && $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i') : '')}}">
            </div>


            {{-- 休憩 --}}
            @php
            $breakIndex = 1;
            $hasEmptyBreak = false;
            $breaks = $work ? $work->breaks : collect();
            @endphp

            @foreach ($breaks as $break)
            @php
            $start = $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : null;
            $end = $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : null;
            $hasEmptyBreak = $hasEmptyBreak|| (!$start && !$end);
            @endphp

            @if ($start || $end)
            <div class="break-form">
                <p class="break-title">休憩{{ $breakIndex}}</p>
                <input class="take-break-time" type="text" name="breaks[{{ $breakIndex }}][start_time]" value="{{ old('breaks.' . $breakIndex . '.start_time', $start) }}">
                <p class="wavy-line">〜</p>
                <input class="break-return-time" type="text" name="breaks[{{ $breakIndex }}][end_time]" value="{{ old('breaks.' . $breakIndex . '.end_time', $end) }}">
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
                <input class="take-break-time" type="text" name="breaks[{{ $breakIndex }}][start_time]" value="">
                <p class="wavy-line">〜</p>
                <input class="break-return-time" type="text" name="breaks[{{ $breakIndex }}][end_time]" value="">
            </div>
            @endif

            {{-- 備考 --}}
            <div class="remark-form">
                <p class="remark-title">備考</p>
                <textarea class="remark" name="remark">{{ old('remark', $work->remarks ?? '')}}</textarea>
                @error('remark')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- 修正ボタン表示 --}}
        <div class="correction-button-form">
            {{-- 未承認の間は修正ボタンを非表示、コメント表示 --}}
            @if ($work && $work->request && $work->request->approved !== 1)
            <p class="text-danger">*承認待ちのため修正はできません。</p>
            @else
            <button class="correction-button" type="submit">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection