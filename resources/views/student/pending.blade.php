@extends('layouts.app')
@section('title','Chờ giáo viên duyệt')
@section('content')
<div style="max-width:520px;margin:80px auto">
    <div class="card center">
        <h2>{{ $session->name }}</h2>
        <p>Xin chào <strong>{{ $participant->name }}</strong></p>
        <div style="font-size:48px;margin:25px 0">⏳</div>
        <h3>Đang chờ giáo viên duyệt</h3>
        <p id="pendingMessage">Giáo viên sẽ xác nhận bạn được tham gia phòng.</p>
    </div>
</div>
@endsection
@section('scripts')
<script>
const stateUrl=@json(route('student.state',['code'=>$session->join_code]));
async function check(){
 const r=await fetch(stateUrl,{headers:{'Accept':'application/json'}});
 const d=await r.json().catch(()=>({}));
 if(d.status==='approved' || d.approved===true){ location.href=@json(route('student.play',['code'=>$session->join_code])); return; }
 if(d.status==='rejected'){ document.getElementById('pendingMessage').textContent=d.message||'Giáo viên đã từ chối bạn vào phòng.'; }
}
setInterval(check,1000); check();
</script>
@endsection
