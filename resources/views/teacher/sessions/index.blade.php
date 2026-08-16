@extends('layouts.app')
@section('title','Cuộc thi')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center">
    <h1>CUỘC THI</h1>
    <a class="btn" href="{{ route('teacher.sessions.create') }}">+ Tạo mới</a>
</div>
<div class="card">
<table>
<thead><tr><th>Tên</th><th>Mã phòng</th><th>Trạng thái</th><th>Học sinh</th><th>Thao tác</th></tr></thead>
<tbody>
@forelse($sessions as $s)
<tr>
<td>{{ $s->name }}</td><td>{{ $s->join_code }}</td><td>{{ $s->status }}</td><td>{{ $s->participants_count }}</td>
<td>
<a class="btn" href="{{ route('teacher.sessions.control',$s) }}">Vào</a>
<a href="{{ route('teacher.sessions.edit',$s) }}">Sửa</a>
</td>
</tr>
@empty
<tr><td colspan="5">Chưa có cuộc thi.</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $sessions->links() }}
@endsection
