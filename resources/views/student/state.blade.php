@extends('layouts.app')
@section('title','Thi')
@section('content')
<div class="card center">
<h2 id="sessionName"></h2>
<p id="studentName"></p>
<h3 id="questionNo">Đang chờ giáo viên...</h3>
<div class="timer" id="timer">00:00</div>
<div id="questionContent"></div>
<div id="answerBox" style="display:none;max-width:650px;margin:20px auto">
<textarea id="answer" placeholder="Đáp án của bạn"></textarea>
<button class="btn mt" onclick="sendAnswer()">GỬI ĐÁP ÁN</button>
</div>
<div id="message">⏳ Đang chờ giáo viên...</div>
</div>
@endsection
@section('scripts')
<script>
const csrf=document.querySelector('meta[name="csrf-token"]').content;
const stateUrl='{{ route('student.state',$session) }}';
const answerUrl='{{ route('student.answer',$session) }}';
let currentQuestion=null, sent=false;
async function poll(){
 const r=await fetch(stateUrl,{headers:{'Accept':'application/json'}});
 if(!r.ok){document.getElementById('message').textContent='Phiên không còn khả dụng.';return}
 const d=await r.json();
 document.getElementById('sessionName').textContent=d.session.name;
 document.getElementById('studentName').textContent=d.participant.name;
 currentQuestion=d.question;
 sent=!!d.answer;
 if(!currentQuestion){resetUI();return}
 document.getElementById('questionNo').textContent='Câu '+currentQuestion.question_number+' / ?';
 document.getElementById('questionContent').textContent=d.show_question?currentQuestion.content:'';
 if(d.state?.status==='running' && d.state.started_at){
   const end=new Date(d.state.started_at).getTime()+currentQuestion.duration*1000;
   const left=Math.max(0,end-Date.now());
   document.getElementById('timer').textContent=format(left);
   if(d.answer){
      document.getElementById('answer').value=d.answer.answer;
      document.getElementById('answerBox').style.display='none';
      document.getElementById('message').textContent='✓ Đã ghi nhận — '+(d.answer.answer_time_ms/1000).toFixed(1)+' giây';
   }else if(left>0){
      document.getElementById('answerBox').style.display='block';
      document.getElementById('message').textContent='🔴 ĐANG TRẢ LỜI';
   }else{
      document.getElementById('answerBox').style.display='none';
      document.getElementById('message').textContent='🔒 HẾT GIỜ';
   }
 }else{
   document.getElementById('answerBox').style.display='none';
   document.getElementById('message').textContent=d.state?.answer_revealed?'Đáp án đã được giáo viên hiện.':'⏳ Đang chờ giáo viên...';
 }
}
async function sendAnswer(){
 if(!currentQuestion)return;
 const answer=document.getElementById('answer').value.trim();
 if(!answer)return alert('Nhập đáp án.');
 const r=await fetch(answerUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({question_id:currentQuestion.id,answer})});
 const d=await r.json();
 if(!r.ok)alert(d.message||'Không thể gửi.');
 poll();
}
function resetUI(){document.getElementById('questionContent').textContent='';document.getElementById('answerBox').style.display='none';}
function format(ms){let s=Math.ceil(ms/1000);return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0')}
setInterval(poll,1000);poll();
</script>
@endsection
