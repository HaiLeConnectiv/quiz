@extends('layouts.app')
@section('title','Tạo phiên')
@section('content')
<h1>TẠO PHIÊN THI</h1>
<div class="card">
<form method="POST" action="{{ route('teacher.sessions.store') }}">
@csrf
<label>Tên phiên</label>
<input name="name" value="{{ old('name') }}" required placeholder="Tăng tốc - Lớp 12A1">
<label class="mt">Mã phòng (để trống = tự tạo)</label>
<input name="join_code" value="{{ old('join_code') }}" placeholder="A7K92">
<label class="mt"><input type="checkbox" name="password_enabled" value="1" style="width:auto" {{ old('password_enabled') ? 'checked' : '' }}> Yêu cầu mật khẩu</label>
<label class="mt">Mật khẩu</label>
<input type="password" name="password">
@if($errors->any())<div class="error mt">{{ $errors->first() }}</div>@endif
<button class="btn mt">TẠO PHIÊN</button>
</form>
</div>
@endsection
