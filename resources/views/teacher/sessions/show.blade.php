@extends('layouts.app')

@section('content')
<div id="game"
     data-state-url="{{ route('teacher.sessions.state', $session) }}"
     data-start-url="{{ route('teacher.sessions.start', $session) }}"
     data-end-url="{{ route('teacher.sessions.end', $session) }}"
     data-answer-url="{{ route('teacher.sessions.show-answer', $session) }}"
     data-next-url="{{ route('teacher.sessions.next', $session) }}"
     data-results-url="{{ route('teacher.sessions.results', $session) }}">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ $session->name }}</h1>
            <div class="mt-1 text-gray-500">
                Mã phòng:
                <span class="font-mono font-bold">{{ $session->join_code }}</span>
            </div>
            <div class="mt-1 text-sm text-gray-500">
                {{ url('/join/'.$session->join_code) }}
            </div>
        </div>

        <div class="rounded-xl bg-white px-5 py-4 shadow">
            👥 <span id="participant-count">0</span> học sinh
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-2xl bg-white p-8 text-center shadow">

            <div id="question-number"
                 class="text-sm font-bold uppercase text-gray-500">
                CÂU 01 / {{ $session->questions->count() }}
            </div>

            <div id="question-content"
                 class="mt-8 min-h-28 text-2xl font-semibold">
                @if($session->currentQuestion)
                    {{ $session->currentQuestion->content }}
                @else
                    Chưa có câu hỏi
                @endif
            </div>

            <div id="timer"
                 class="mt-8 text-7xl font-black tracking-tight">
                00:30
            </div>

            <div id="game-status"
                 class="mt-4 text-lg font-medium text-gray-500">
                ĐANG CHỜ GIÁO VIÊN
            </div>

            <div id="correct-answer"
                 class="mt-6 hidden rounded-xl bg-green-50 p-5 text-xl font-bold text-green-800">
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <button id="btn-start"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">
                    ▶ BẮT ĐẦU TRẢ LỜI
                </button>

                <button id="btn-end"
                        class="hidden rounded-lg bg-red-600 px-6 py-3 font-bold text-white">
                    KẾT THÚC
                </button>

                <button id="btn-show-answer"
                        class="hidden rounded-lg bg-green-600 px-6 py-3 font-bold text-white">
                    HIỆN ĐÁP ÁN
                </button>

                <button id="btn-next"
                        class="hidden rounded-lg bg-gray-900 px-6 py-3 font-bold text-white">
                    CÂU TIẾP THEO →
                </button>

                <a href="{{ route('teacher.sessions.results', $session) }}"
                   class="rounded-lg bg-gray-200 px-6 py-3 font-bold">
                    XEM KẾT QUẢ
                </a>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">HỌC SINH</h2>
                <span id="answered-count"
                      class="rounded-full bg-gray-100 px-3 py-1 text-sm">
                    0 / 0
                </span>
            </div>

            <div id="participants"
                 class="mt-4 divide-y">
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
const game = document.getElementById('game');
const csrf = document.querySelector('meta[name="csrf-token"]').content;

let countdownAnimation = null;
let lastStartedAt = null;

const urls = {
    state: game.dataset.stateUrl,
    start: game.dataset.startUrl,
    end: game.dataset.endUrl,
    answer: game.dataset.answerUrl,
    next: game.dataset.nextUrl,
    results: game.dataset.resultsUrl
};

function formatSeconds(seconds) {
    seconds = Math.max(0, Math.ceil(seconds));
    const m = String(Math.floor(seconds / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    return `${m}:${s}`;
}

function startCountdown(startedAt, duration) {
    cancelAnimationFrame(countdownAnimation);

    if (!startedAt) {
        document.getElementById('timer').innerText =
            formatSeconds(duration);
        return;
    }

    const start = new Date(startedAt).getTime();
    const end = start + duration * 1000;

    function tick() {
        const remaining = Math.max(0, end - Date.now());

        document.getElementById('timer').innerText =
            formatSeconds(remaining / 1000);

        if (remaining > 0) {
            countdownAnimation = requestAnimationFrame(tick);
        }
    }

    tick();
}

function render(data) {
    document.getElementById('participant-count').innerText =
        data.participant_count ?? 0;

    document.getElementById('answered-count').innerText =
        `${data.answered_count ?? 0} / ${data.participant_count ?? 0}`;

    if (data.question) {
        document.getElementById('question-number').innerText =
            `CÂU ${String(data.question.number).padStart(2, '0')} / ${data.total_questions}`;

        document.getElementById('question-content').innerText =
            data.question.content;

        if (
            data.started_at &&
            data.question_state === 'running'
        ) {
            startCountdown(
                data.started_at,
                data.question.duration
            );
        } else if (data.question_state === 'ended' ||
                   data.question_state === 'show_answer') {
            document.getElementById('timer').innerText = '00:00';
        }
    }

    renderStatus(data);
    renderButtons(data);
    renderParticipants(data);
}

function renderStatus(data) {
    const el = document.getElementById('game-status');

    const labels = {
        waiting: '⏳ ĐANG CHỜ GIÁO VIÊN',
        running: '🔴 ĐANG NHẬN CÂU TRẢ LỜI',
        ended: '🔒 HẾT GIỜ',
        show_answer: '✓ ĐÁP ÁN'
    };

    el.innerText =
        labels[data.question_state] ||
        'ĐANG CHỜ GIÁO VIÊN';

    const answer = document.getElementById('correct-answer');

    if (
        data.question_state === 'show_answer' &&
        data.question?.correct_answer
    ) {
        answer.classList.remove('hidden');
        answer.innerText =
            `Đáp án đúng: ${data.question.correct_answer}`;
    } else {
        answer.classList.add('hidden');
    }
}

function renderButtons(data) {
    const start = document.getElementById('btn-start');
    const end = document.getElementById('btn-end');
    const answer = document.getElementById('btn-show-answer');
    const next = document.getElementById('btn-next');

    start.classList.add('hidden');
    end.classList.add('hidden');
    answer.classList.add('hidden');
    next.classList.add('hidden');

    if (data.question_state === 'waiting') {
        start.classList.remove('hidden');
    }

    if (data.question_state === 'running') {
        end.classList.remove('hidden');
    }

    if (data.question_state === 'ended') {
        answer.classList.remove('hidden');
    }

    if (data.question_state === 'show_answer') {
        next.classList.remove('hidden');
    }
}

function renderParticipants(data) {
    const container =
        document.getElementById('participants');

    container.innerHTML = '';

    for (const participant of data.participants ?? []) {
        const row = document.createElement('div');
        row.className = 'py-3 flex items-center justify-between gap-3';

        let status = 'Chưa trả lời';
        let className = 'text-gray-500';

        if (participant.answered) {
            const time =
                (participant.answer_time_ms / 1000).toFixed(1);

            status = `Đã trả lời - ${time}s`;
            className = 'font-medium text-green-700';
        }

        row.innerHTML = `
            <span class="font-medium">${escapeHtml(participant.name)}</span>
            <span class="${className} text-sm">${status}</span>
        `;

        container.appendChild(row);
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.innerText = value ?? '';
    return div.innerHTML;
}

async function action(url) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        }
    });

    const data = await response.json();

    if (!response.ok) {
        alert(data.message || 'Có lỗi xảy ra.');
        return false;
    }

    return true;
}

document.getElementById('btn-start').onclick = async () => {
    if (await action(urls.start)) {
        poll();
    }
};

document.getElementById('btn-end').onclick = async () => {
    if (await action(urls.end)) {
        poll();
    }
};

document.getElementById('btn-show-answer').onclick = async () => {
    if (await action(urls.answer)) {
        poll();
    }
};

document.getElementById('btn-next').onclick = async () => {
    if (await action(urls.next)) {
        poll();
    }
};

async function poll() {
    try {
        const response = await fetch(urls.state, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) return;

        render(await response.json());
    } catch (error) {
        console.error(error);
    }
}

setInterval(poll, 1000);
poll();
</script>
@endpush
