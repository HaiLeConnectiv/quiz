@extends('layouts.app')
@section('title', 'Kết quả - ' . $session->name)

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div>
            <h1>KẾT QUẢ: {{ $session->name }}</h1>
            <p>Mã phòng: <strong>{{ $session->join_code }}</strong></p>
        </div>

        <div>
            <a class="btn success" href="{{ route('teacher.sessions.export', $session) }}">
                Xuất Excel/CSV
            </a>
            <a class="btn secondary" href="{{ route('teacher.sessions.control', $session) }}">
                ← Điều khiển
            </a>
        </div>
    </div>

    {{-- <div class="card">
    <h2>Xếp hạng</h2>

    <table>
        <thead>
        <tr>
            <th>Hạng</th>
            <th>Học sinh</th>
            <th>Đúng</th>
            <th>Sai</th>
            <th>Bỏ qua</th>
            <th>Tổng thời gian đúng</th>
        </tr>
        </thead>
        <tbody>
        @forelse($summary as $index => $item)
            <tr>
                <td>
                    @if ($index === 0) 🥇
                    @elseif($index === 1) 🥈
                    @elseif($index === 2) 🥉
                    @else {{ $index + 1 }}
                    @endif
                </td>
                <td><strong>{{ $item['participant']->name }}</strong></td>
                <td>{{ $item['correct'] }}</td>
                <td>{{ $item['wrong'] }}</td>
                <td>{{ $item['unanswered'] }}</td>
                <td>{{ number_format($item['total_time_ms'] / 1000, 1) }}s</td>
            </tr>
        @empty
            <tr><td colspan="6">Chưa có học sinh.</td></tr>
        @endforelse
        </tbody>
    </table>
</div> --}}

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap">
            <h2>Chi tiết câu trả lời</h2>

            <form method="GET" action="{{ route('teacher.sessions.results', $session) }}">
                <label style="display:inline-block">
                    Câu hỏi:
                    <select name="question_id" onchange="this.form.submit()" style="width:250px;display:inline-block">
                        <option value="">Tất cả câu</option>
                        @foreach ($session->questions as $q)
                            <option value="{{ $q->id }}"
                                {{ (string) $questionId === (string) $q->id ? 'selected' : '' }}>
                                Câu {{ $q->question_number }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        {{-- <p>
        Hiển thị <strong>{{ $detailRows->count() }}</strong> dòng
        — mỗi học sinh được tính cả trường hợp chưa trả lời.
    </p> --}}

        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Câu</th>
                        <th>Học sinh</th>
                        <th>Đáp án</th>
                        {{-- <th>Đáp án đúng</th> --}}
                        <th>Thời gian</th>
                        {{-- <th>Kết quả</th> --}}
                    </tr>
                </thead>

                <tbody>
                    @forelse($detailRows as $row)
                        <tr>
                            <td>
                                <strong>Câu {{ $row['question']->question_number }}</strong>
                            </td>

                            <td>{{ $row['participant']->name }}</td>

                            <td>
                                @if ($row['answer'])
                                    {{ $row['answer_text'] }}
                                @else
                                    <span style="color:#94a3b8">—</span>
                                @endif
                            </td>

                            {{-- <td>{{ $row['question']->correct_answer }}</td> --}}

                            <td>
                                @if ($row['answer_time_ms'] !== null)
                                    <strong>{{ number_format($row['answer_time_ms'] / 1000, 1) }}s</strong>
                                    <small style="color:#64748b">
                                        ({{ number_format($row['answer_time_ms']) }} ms)
                                    </small>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- <td>
                        @if ($row['status'] === 'correct')
                            <span style="color:#16a34a;font-weight:bold">✓ Đúng</span>
                        @elseif($row['status'] === 'wrong')
                            <span style="color:#dc2626;font-weight:bold">✕ Sai</span>
                        @else
                            <span style="color:#64748b">Chưa trả lời</span>
                        @endif
                    </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
