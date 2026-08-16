@extends('layouts.app')
@section('title','Thiết lập phiên')
@section('content')
<h1>THIẾT LẬP: {{ $session->name }}</h1>
<div class="card">
<p>Link học sinh: <strong>{{ url('/join/'.$session->join_code) }}</strong></p>
<form method="POST" action="{{ route('teacher.sessions.update',$session) }}">
@csrf @method('PUT')
<label>Tên phiên</label><input name="name" value="{{ $session->name }}" required>
<label class="mt"><input type="checkbox" name="password_enabled" value="1" style="width:auto" {{ $session->password_enabled?'checked':'' }}> Yêu cầu mật khẩu</label>
<label class="mt">Mật khẩu</label><input type="password" name="password" placeholder="Để trống để giữ mật khẩu hiện tại">
<button class="btn mt">LƯU</button>
</form>
</div>
<div class="card">
<h2>Thêm câu hỏi</h2>
<form method="POST" action="{{ route('teacher.sessions.questions.store',$session) }}">
@csrf
<label>Nội dung</label><textarea name="content" required></textarea>
<label class="mt">Đáp án đúng</label><input name="correct_answer" required>
<label class="mt">Thời gian (giây)</label><input type="number" name="duration" value="30" min="1" required>
<button class="btn mt">THÊM CÂU</button>
</form>
</div>
<div class="card">
<h2>Câu hỏi</h2>
@foreach($session->questions as $q)
<p><strong>Câu {{ $q->question_number }}:</strong> {{ $q->content }} — {{ $q->duration }}s</p>
@endforeach
<a class="btn success" href="{{ route('teacher.sessions.control',$session) }}">MỞ MÀN HÌNH ĐIỀU KHIỂN</a>
</div>
@endsection
