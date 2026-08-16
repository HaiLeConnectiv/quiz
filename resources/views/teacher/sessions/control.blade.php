@extends('layouts.app')
@section('title', 'Điều khiển cuộc thi')
@section('content')
    <div style="display:flex;justify-content:space-between">
        <h1>{{ $session->name }}</h1>
        <div>👥 <span id="participants">{{ $session->participants->count() }}</span> học sinh</div>
    </div>

    <div class="card center">
        <h2 id="question" style="display:none">Chưa chọn câu hỏi</h2>
        <div class="timer" id="timer">00:00</div>
        <div id="stateText">Chờ giáo viên.</div>
        <div class="mt">
            <select id="questionSelect">
                <option value="">-- Chọn câu --</option>
                @foreach ($session->questions as $q)
                    <option value="{{ $q->id }}">Câu {{ $q->question_number }} — {{ Str::limit($q->content, 80) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mt">
            <button class="btn success" onclick="startQuestion()">▶ BẮT ĐẦU TRẢ LỜI</button>
            <button class="btn danger" onclick="postAction('{{ route('teacher.sessions.end', $session) }}')">KẾT
                THÚC</button>
            <button class="btn secondary" onclick="postAction('{{ route('teacher.sessions.reveal', $session) }}')">HIỆN ĐÁP
                ÁN</button>
            <a class="btn" href="{{ route('teacher.sessions.results', $session) }}">KẾT QUẢ</a>
            <button class="btn" onclick="nextQuestion()">CÂU TIẾP THEO →</button>
        </div>
    </div>


    {{-- <div class="card">
        <h2>Học sinh tham gia</h2>
        <div style="margin-bottom:15px">Đang chờ duyệt: <strong id="pendingCount">0</strong></div>
        <div id="pendingList">
            <p style="color:#64748b">Không có học sinh chờ duyệt.</p>
        </div>
        <hr>
        <div>Đã duyệt: <strong id="approvedCount">0</strong></div>
    </div>

    <div class="card">
        <h2>Học sinh</h2>
        <div>Đã trả lời: <strong id="answered">0</strong> / <strong
                id="total">{{ $session->participants->count() }}</strong></div>
    </div> --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <h2>
                HỌC SINH
                <span style="font-size:14px;color:#64748b">
                    ({{ $onlineParticipants->count() }} online)
                </span>
            </h2>

            <span style="color:#16a34a">
                ● Đang online
            </span>
        </div>

        @forelse($onlineParticipants as $participant)
            <div
                style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px 0;
            border-bottom:1px solid #e5e7eb;
        ">

                <div>
                    <strong>{{ $participant->name }}</strong>

                    @if ($participant->status === 'pending')
                        <div style="color:#d97706;font-size:14px">
                            ⏳ Đang chờ phê duyệt
                        </div>
                    @else
                        <div style="color:#16a34a;font-size:14px">
                            ● Đang thi
                        </div>
                    @endif
                </div>

                @if ($participant->status === 'pending')
                    <div>
                        <form method="POST"
                            action="{{ route('teacher.sessions.participants.approve', [$session, $participant]) }}"
                            style="display:inline">
                            @csrf

                            <button class="btn success">
                                ✓ Duyệt
                            </button>
                        </form>

                        <form method="POST"
                            action="{{ route('teacher.sessions.participants.reject', [$session, $participant]) }}"
                            style="display:inline">
                            @csrf

                            <button class="btn danger">
                                ✕ Từ chối
                            </button>
                        </form>
                    </div>
                @else
                    <span
                        style="
                    display:inline-block;
                    width:10px;
                    height:10px;
                    border-radius:50%;
                    background:#22c55e;
                "></span>
                @endif

            </div>

        @empty

            <div style="
            padding:30px;
            text-align:center;
            color:#64748b;
        ">
                Chưa có học sinh online
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const stateUrl = '{{ route('teacher.sessions.state', $session) }}';
        const startUrl = '{{ route('teacher.sessions.start', $session) }}';
        const nextUrl = '{{ route('teacher.sessions.next', $session) }}';
        async function postAction(url, body = {}) {
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(body)
            });
            if (!r.ok) alert((await r.text()) || 'Có lỗi');
            poll();
        }
        async function startQuestion() {
            const id = document.getElementById('questionSelect').value;
            if (!id) return alert('Chọn câu hỏi.');
            await postAction(startUrl, {
                question_id: Number(id)
            });
        }
        async function nextQuestion() {
            await postAction(nextUrl);
        }
        async function poll() {
            const r = await fetch(stateUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const d = await r.json();

            const participantsEl = document.getElementById('participants');
            if (participantsEl) participantsEl.textContent = d.participants_count ?? 0;

            const approvedEl = document.getElementById('approvedCount');
            if (approvedEl) approvedEl.textContent = d.participants_count ?? 0;

            const pendingEl = document.getElementById('pendingCount');
            if (pendingEl) pendingEl.textContent = d.pending_count ?? 0;

            renderPending(d.pending_participants || []);

            const totalEl = document.getElementById('total');
            if (totalEl) totalEl.textContent = d.participants_count ?? 0;

            const answeredEl = document.getElementById('answered');
            if (answeredEl) answeredEl.textContent = d.answered_count ?? 0;

            const questionEl = document.getElementById('question');
            const timerEl = document.getElementById('timer');
            const stateTextEl = document.getElementById('stateText');

            if (!d.question) {
                if (questionEl) {
                    questionEl.textContent = '';
                    questionEl.style.display = 'none';
                }
                if (timerEl) timerEl.textContent = '00:00';
                if (stateTextEl) stateTextEl.textContent = 'Chờ giáo viên.';
                return;
            }

            if (questionEl) {
                const selectedQuestionText = document.getElementById('questionSelect')?.selectedOptions?.[0]?.textContent || '';
                const selectedQuestionValue = document.getElementById('questionSelect')?.value;

                questionEl.textContent = selectedQuestionText || (
                    d.question ? 'Câu ' + d.question.question_number + ': ' + d.question.content : ''
                );
                questionEl.style.display = selectedQuestionValue || d.question ? 'block' : 'none';
            }

            if (d.state?.status === 'running' && d.state.started_at) {
                const start = new Date(d.state.started_at).getTime();
                const end = start + (d.question.duration * 1000);
                const left = Math.max(0, end - Date.now());
                if (timerEl) timerEl.textContent = format(left);
                if (stateTextEl) stateTextEl.textContent = left > 0 ? '🔴 ĐANG NHẬN CÂU TRẢ LỜI' : '🔒 HẾT GIỜ';
                return;
            }

            if (timerEl) timerEl.textContent = '00:00';
            if (stateTextEl) stateTextEl.textContent = 'Chờ giáo viên Start';
        }
        async function approveParticipant(id, action) {
            const url = action === 'approve' ?
                `{{ url('/teacher/sessions/' . $session->id . '/participants') }}/${id}/approve` :
                `{{ url('/teacher/sessions/' . $session->id . '/participants') }}/${id}/reject`;
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            });
            if (!r.ok) alert('Không thể cập nhật học sinh.');
            poll();
        }

        function renderPending(items) {
            const el = document.getElementById('pendingList');
            if (!el) return;
            if (!items.length) {
                el.innerHTML = '<p style="color:#64748b">Không có học sinh chờ duyệt.</p>';
                return;
            }
            el.innerHTML = items.map(p =>
                `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #eee;gap:10px"><strong>${escapeHtml(p.name)}</strong><span><button class="btn success" onclick="approveParticipant(${p.id},'approve')">✓ Duyệt</button> <button class="btn danger" onclick="approveParticipant(${p.id},'reject')">✕ Từ chối</button></span></div>`
            ).join('');
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>'"]/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            } [c]));
        }

        function format(ms) {
            let s = Math.ceil(ms / 1000);
            return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')
        }
        setInterval(poll, 1000);
        poll();
    </script>
@endsection
