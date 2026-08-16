@extends('layouts.app')
@section('title', $session->name)
@section('content')
<div class="card center">
    <h2>{{ $session->name }}</h2>
    <p>Học sinh: <strong>{{ $participant->name }}</strong></p>

    <div id="waiting">
        <h3 id="questionNo">Chờ giáo viên...</h3>
        <div class="timer" id="timer">00:00</div>
        <div id="message">⏳ Đang chờ giáo viên bắt đầu câu hỏi...</div>
    </div>

    <div id="quiz" style="display:none">
        <h3 id="quizQuestion"></h3>
        <div class="timer" id="quizTimer">00:00</div>

        <div id="answerForm" style="max-width:650px;margin:20px auto">
            <textarea id="answer" placeholder="Nhập đáp án của bạn"></textarea>
            <button class="btn success mt" onclick="sendAnswer()">GỬI ĐÁP ÁN</button>
        </div>

        <div id="submitted" style="display:none">
            <p>✓ Đã ghi nhận</p>
            <p>Thời gian: <strong id="answerTime"></strong></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const stateUrl = @json(route('student.state', ['code' => $session->join_code]));
const answerUrl = @json(route('student.answer', ['code' => $session->join_code]));

let currentQuestion = null;
let lastQuestionId = null;
let submitted = false;

async function poll() {
    const response = await fetch(stateUrl, {
        headers: {'Accept': 'application/json'}
    });

    if (!response.ok) {
        document.getElementById('message').textContent = 'Phiên thi không còn khả dụng.';
        return;
    }

    const data = await response.json();

    currentQuestion = data.question;

    if (!currentQuestion) {
        document.getElementById('waiting').style.display = 'block';
        document.getElementById('quiz').style.display = 'none';
        document.getElementById('questionNo').textContent = 'Chờ giáo viên...';
        document.getElementById('message').textContent = '⏳ Đang chờ giáo viên bắt đầu câu hỏi...';
        return;
    }

    const state = data.state;

    if (lastQuestionId !== currentQuestion.id) {
        lastQuestionId = currentQuestion.id;
        submitted = !!data.answer;
        document.getElementById('answer').value = data.answer?.answer ?? '';
        document.getElementById('answerForm').style.display = 'block';
        document.getElementById('submitted').style.display = data.answer ? 'block' : 'none';
        if (data.answer) {
            document.getElementById('answerTime').textContent =
                (data.answer.answer_time_ms / 1000).toFixed(1) + ' giây';
        }
    }

    if (state && state.status === 'running' && state.started_at) {
        const start = new Date(state.started_at).getTime();
        const end = start + currentQuestion.duration * 1000;
        const left = Math.max(0, end - Date.now());

        document.getElementById('waiting').style.display = 'none';
        document.getElementById('quiz').style.display = 'block';
        document.getElementById('quizQuestion').textContent =
            'Câu ' + currentQuestion.question_number + ': ' + currentQuestion.content;
        document.getElementById('quizTimer').textContent = formatTime(left);

        if (data.answer) {
            document.getElementById('answerForm').style.display = 'none';
            document.getElementById('submitted').style.display = 'block';
        } else if (left <= 0) {
            document.getElementById('answerForm').style.display = 'none';
            document.getElementById('submitted').style.display = 'block';
            document.getElementById('submitted').innerHTML = '<p>🔒 HẾT GIỜ</p><p>Chờ giáo viên...</p>';
        } else {
            document.getElementById('answerForm').style.display = 'block';
        }

        return;
    }

    document.getElementById('waiting').style.display = 'block';
    document.getElementById('quiz').style.display = 'none';
    document.getElementById('questionNo').textContent = 'Chờ giáo viên...';
    document.getElementById('message').textContent =
        state?.answer_revealed
            ? 'Đáp án đã được giáo viên hiện.'
            : '⏳ Đang chờ giáo viên bắt đầu câu hỏi...';
}

async function sendAnswer() {
    if (!currentQuestion) return;

    const answer = document.getElementById('answer').value.trim();

    if (!answer) {
        alert('Vui lòng nhập đáp án.');
        return;
    }

    const response = await fetch(answerUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            question_id: currentQuestion.id,
            answer: answer
        })
    });

    const data = await response.json();

    if (!response.ok) {
        alert(data.message || 'Không thể gửi đáp án.');
        return;
    }

    submitted = true;
    document.getElementById('answerForm').style.display = 'none';
    document.getElementById('submitted').style.display = 'block';
    document.getElementById('answerTime').textContent =
        (data.answer_time_ms / 1000).toFixed(1) + ' giây';

    poll();
}

function formatTime(ms) {
    const seconds = Math.ceil(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const remain = seconds % 60;

    return String(minutes).padStart(2, '0') + ':' +
           String(remain).padStart(2, '0');
}

setInterval(poll, 1000);
poll();
</script>
@endsection
