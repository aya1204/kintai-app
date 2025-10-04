{{-- スタッフ一覧画面を表示するBlade --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/user/staff_list.css') }}">
@endsection

@section('content')

@if(session('success'))
<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="staff-list-page">
    <ul class="staff-list-page-title-form">
        <li class="staff-list-page-title">
            <p class="staff-list-title">スタッフ一覧</p>
        </li>
    </ul>

    <div class="staff-list-form">
        <div class="staff-list-title-form">
            <p class="name-title">名前</p>
            <p class="email-title">メールアドレス</p>
            <p class="detail-title">月次勤怠</p>
        </div>
        @foreach($users as $user)
        <div class="staff-list-row {{ $loop->last ? 'last-staff' : '' }}">
            <p class="user-name">
                {{ $user->name }}
            </p>
            <p class="email">
                {{ $user->email }}
            </p>
            <p class="detail">
                <a class="detail-link" href="{{ route('admin.staff.attendance.list', ['user' => $user->id]) }}">詳細</a>
            </p>
        </div>
        @endforeach
    </div>
</div>
@endsection