@extends('layouts.app')
@section('title','Tham gia cuộc thi')
@section('content')
<div style="max-width:480px;margin:60px auto">
    <div class="card">
        <h2>{{ $session->name }}</h2>
        <p>Mã phòng: <strong>{{ $session->join_code }}</strong></p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('student.join.store', $session->join_code) }}">
            @csrf

            <label>Họ tên</label>
            <input name="name" value="{{ old('name') }}" required autofocus>

            @if($session->password_enabled)
                <label class="mt">Mật khẩu phòng</label>
                <input type="password" name="password" required>
            @endif

            <button class="btn mt" style="width:100%">GỬI YÊU CẦU THAM GIA</button>
        </form><p style="margin-top:12px;color:#64748b;text-align:center">Sau khi gửi, bạn sẽ chờ giáo viên duyệt vào phòng.</p>
    </div>
</div>
@endsection
