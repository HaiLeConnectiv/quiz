@extends('layouts.app')
@section('title','Đăng nhập giáo viên')
@section('content')
<div style="max-width:420px;margin:70px auto">
    <div class="card">
        <h2>Đăng nhập giáo viên</h2>
        @if($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label class="mt">Mật khẩu</label>
            <input type="password" name="password" required>
            <label class="mt"><input type="checkbox" name="remember" value="1" style="width:auto"> Ghi nhớ</label>
            <button class="btn mt" style="width:100%">ĐĂNG NHẬP</button>
        </form>
    </div>
</div>
@endsection
