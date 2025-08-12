{{-- 申請一覧画面のBladeファイル --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/request/list.css') }}">
@endsection

@section('content')
<div class="request-form">
    <ul class="list-title-form">
        <li class="list-title">申請一覧</li>
    </ul>
    <div class="tab-buttons">
        <a href="{{ route('staff.request.list', ['tab' => 'wait'])}}" class="button-submit">承認待ち</a>
        <a href="{{ route('staff.request.list', ['tab' => 'clear'])}}" class="button-submit">承認済み</a>
    </div>

    <div class="request-list-form">
        <div class="request-list-title-form">
            <p class="status-title">状態</p>
            <p class="name-title">名前</p>
            <p class="work-date-title">対象日時</p>
            <p class="request-reason-title">申請理由</p>
            <p class="request-date-title">申請日時</p>
            <p class="detail-title">詳細</p>
        </div>
        <div class="request-list">
            @foreach ($requests as $request)
            <div class="request-list-item">
                <p class="status">{{ $request->approved ? '承認済み' : '承認待ち'}}</p>
                <p class="user-name">
                    {{ $user->name }}
                </p>
                <p class="work-date">{{ \Carbon\Carbon::parse($request->requestWork->date)->format('Y/m/d') }}</p>
                <p class="reason">{{ $request->staff_remarks }}</p>
                <p class="request-date">{{ $request->created_at->format('Y/m/d') }}</p>
                @if($request->work_id)
                <a class="detail" href="{{ route('staff.attendance.detail', ['work' =>$request->work_id]) }}">詳細</a>
                @else
                <a class="detail" href="{{ route('staff.attendance.createForm', ['date' => $request->requestWork->date ?? now()->toDateString()]) }}">詳細</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection