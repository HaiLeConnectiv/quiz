<div id="student-game"
     data-state-url="{{ route('student.state', $session->join_code) }}"
     data-answer-url="{{ route('student.answer', $session->join_code) }}">

    <div class="rounded-2xl bg-white p-6 shadow">
        <div class="text-center">
            <h1 class="text-xl font-bold">
                {{ $session->name }}
            </h1>

            <div class="mt-2 text-gray-500">
                {{ $participant->name }}
            </div>
        </div>

        <div id="student-question-number"
             class="mt-10 text-center text-sm font-bold text-gray-500">
            CÂU 01 / ?
        </div>

        <div id="student-question-content"
             class="mt-6 min-h-16 text-center text-xl font-semibold">
        </div>

        <div id="student-timer"
             class="mt-8 text-center text-7xl font-black">
            00:30
        </div>

        <div id="student-waiting"
             class="mt-8 rounded-xl bg-gray-100 p-5 text-center text-lg">
            ⏳ Đang chờ giáo viên...
        </div>

        <div id="student-answer-area"
             class="mt-8 hidden">
            <textarea id="student-answer"
                      rows="5"
                      class="w-full rounded-xl border p-4 text-lg"
                      placeholder="Nhập đáp án của bạn..."></textarea>

            <button id="student-submit"
                    class="mt-4 w-full rounded-xl bg-blue-600 px-5 py-4 text-lg font-bold text-white">
                GỬI ĐÁP ÁN
            </button>
        </div>

        <div id="student-submitted"
             class="mt-8 hidden rounded-xl bg-green-50 p-6 text-center">
            <div class="text-xl font-bold text-green-700">
                ✓ Đã ghi nhận
            </div>

            <div id="student-answer-time"
                 class="mt-2 text-green-700">
            </div>
        </div>

        <div id="student-ended"
             class="mt-8 hidden rounded-xl bg-gray-100 p-6 text-center">
            <div class="text-xl font-bold">
                🔒 HẾT GIỜ
            </div>

            <div class="mt-2">
                Đáp án đã được ghi nhận.
            </div>
        </div>

        <div id="student-result"
             class="mt-8 hidden rounded-xl p-6 text-center">
        </div>
    </div>
</div>

@push('scripts')
<script>
const studentGame =
    document.getElementById('student-game');

const studentStateUrl =
    studentGame.dataset.stateUrl;

const studentAnswerUrl =
    studentGame.dataset.answerUrl;

const studentCsrf =
    document.querySelector('meta[name="csrf-token"]').content;

let studentAnimation = null;

function formatTime(seconds) {
    seconds = Math.max(0, Math.ceil(seconds));

    const minutes =
        String(Math.floor(seconds / 60)).padStart(2, '0');

    const secs =
        String(seconds % 60).padStart(2, '0');

    return `${minutes}:${secs}`;
}

function countdown(startedAt, duration) {
    cancelAnimationFrame(studentAnimation);

    const start =
        new Date(startedAt).getTime();

    const end =
        start + duration * 1000;

    function tick() {
        const remaining =
            Math.max(0, end - Date.now());

        document.getElementById(
            'student-timer'
        ).innerText =
            formatTime(remaining / 1000);

        if (remaining > 0) {
            studentAnimation =
                requestAnimationFrame(tick);
        }
    }

    tick();
}

function hideAll() {
    document.getElementById('student-waiting')
        .classList.add('hidden');

    document.getElementById('student-answer-area')
        .classList.add('hidden');

    document.getElementById('student-submitted')
        .classList.add('hidden');

    document.getElementById('student-ended')
        .classList.add('hidden');

    document.getElementById('student-result')
        .classList.add('hidden');
}

function render(data) {
    hideAll();

    if (data.question) {
        document.getElementById(
            'student-question-number'
        ).innerText =
            `CÂU ${String(data.question.number).padStart(2, '0')}`;

        document.getElementById(
            'student-question-content'
        ).innerText =
            data.question.content || '';
    }

    if (data.status === 'waiting') {
        document.getElementById('student-waiting')
            .classList.remove('hidden');

        return;
    }

    if (data.status === 'running') {

        if (
            data.answer &&
            data.answer.submitted
        ) {
            showSubmitted(data);
        } else {
            document.getElementById(
                'student-answer-area'
            ).classList.remove('hidden');
        }

        if (data.started_at) {
            countdown(
                data.started_at,
                data.question.duration
            );
        }

        return;
    }

    if (data.status === 'ended') {

        if (
            data.answer &&
            data.answer.submitted
        ) {
            showSubmitted(data);
        } else {
            document.getElementById(
                'student-ended'
            ).classList.remove('hidden');
        }

        document.getElementById(
            'student-timer'
        ).innerText = '00:00';

        return;
    }

    if (data.status === 'show_answer') {

        document.getElementById(
            'student-ended'
        ).classList.remove('hidden');

        if (
            data.answer &&
            data.answer.submitted
        ) {
            showSubmitted(data);
        }

        if (
            data.answer &&
            data.answer.is_correct !== null
        ) {
            const result =
                document.getElementById('student-result');

            result.classList.remove('hidden');

            if (data.answer.is_correct) {
                result.className =
                    'mt-8 rounded-xl bg-green-100 p-6 text-center text-green-800';

                result.innerText = '✓ ĐÚNG';
            } else {
                result.className =
                    'mt-8 rounded-xl bg-red-100 p-6 text-center text-red-800';

                result.innerText = '✕ SAI';
            }
        }
    }
}

function showSubmitted(data) {
    document.getElementById(
        'student-submitted'
    ).classList.remove('hidden');

    document.getElementById(
        'student-answer-time'
    ).innerText =
        `Thời gian: ${(data.answer.answer_time_ms / 1000).toFixed(1)} giây`;
}

document.getElementById(
    'student-submit'
).addEventListener('click', async function() {

    const button = this;

    const textarea =
        document.getElementById('student-answer');

    const answer =
        textarea.value.trim();

    if (!answer) {
        alert('Vui lòng nhập đáp án.');
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(
            studentAnswerUrl,
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/json',

                    'X-CSRF-TOKEN':
                        studentCsrf,

                    'Accept':
                        'application/json'
                },
                body: JSON.stringify({
                    answer
                })
            }
        );

        const data =
            await response.json();

        if (!response.ok) {
            alert(
                data.message ||
                'Không thể gửi đáp án.'
            );

            button.disabled = false;
            return;
        }

        pollStudent();

    } catch (error) {
        alert('Không thể kết nối máy chủ.');
        button.disabled = false;
    }
});

async function pollStudent() {
    try {
        const response =
            await fetch(studentStateUrl, {
                headers: {
                    'Accept':
                        'application/json'
                }
            });

        if (!response.ok) {
            return;
        }

        const data =
            await response.json();

        render(data);

    } catch (error) {
        console.error(error);
    }
}

setInterval(
    pollStudent,
    1000
);

pollStudent();
</script>
@endpush
