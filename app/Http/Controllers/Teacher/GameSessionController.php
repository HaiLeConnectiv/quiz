<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\GameQuestionState;
use App\Models\GameSession;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class GameSessionController extends Controller
{
    public function index()
    {
        $sessions = GameSession::where('created_by', auth()->id())
            ->withCount('participants')
            ->latest()
            ->paginate(20);

        return view('teacher.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('teacher.sessions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'join_code' => ['nullable', 'string', 'alpha_num', 'min:4', 'max:20', 'unique:game_sessions,join_code'],
            'password_enabled' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ]);

        $enabled = $request->boolean('password_enabled');

        if ($enabled && empty($data['password'])) {
            return back()->withErrors(['password' => 'Vui lòng nhập mật khẩu phòng.'])->withInput();
        }

        $session = GameSession::create([
            'name' => $data['name'],
            'join_code' => strtoupper($data['join_code'] ?: $this->makeCode()),
            'password' => $enabled ? $data['password'] : null,
            'password_enabled' => $enabled,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('teacher.sessions.edit', $session);
    }

    public function edit(GameSession $session)
    {
        $this->authorizeSession($session);
        $session->load('questions');

        return view('teacher.sessions.edit', compact('session'));
    }

    public function update(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password_enabled' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ]);

        $enabled = $request->boolean('password_enabled');

        if ($enabled && empty($data['password']) && !$session->password) {
            return back()->withErrors(['password' => 'Vui lòng nhập mật khẩu phòng.'])->withInput();
        }

        $session->update([
            'name' => $data['name'],
            'password_enabled' => $enabled,
            'password' => $enabled ? ($data['password'] ?: $session->password) : null,
        ]);

        return back()->with('success', 'Đã cập nhật phiên.');
    }

    public function control(GameSession $session)
    {
        $this->authorizeSession($session);

        $session->load([
            'questions',
            'currentQuestion',
        ]);

        $onlineParticipants = $session->participants()
            ->whereIn('status', ['pending', 'approved'])
            ->where('last_seen_at', '>=', now()->subSeconds(5))
            ->orderByRaw("
            CASE
                WHEN status = 'pending' THEN 0
                ELSE 1
            END
        ")
            ->orderBy('name')
            ->get();

        return view('teacher.sessions.control', compact(
            'session',
            'onlineParticipants'
        ));
    }

    // public function control(GameSession $session)
    // {
    //     $this->authorizeSession($session);
    //     $session->load(['questions', 'participants']);

    //     return view('teacher.sessions.control', compact('session'));
    // }

    public function state(GameSession $session)
    {
        $this->authorizeSession($session);

        $session->load('currentQuestion');

        $onlineParticipants = $session->participants()
            ->whereIn('status', ['pending', 'approved'])
            ->where('last_seen_at', '>=', now()->subSeconds(5))
            ->orderByRaw("
            CASE
                WHEN status = 'pending' THEN 0
                ELSE 1
            END
        ")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'status',
                'last_seen_at',
            ]);

        $state = $session->current_question_id
            ? GameQuestionState::where('game_session_id', $session->id)
                ->where('question_id', $session->current_question_id)
                ->latest('id')
                ->first()
            : null;

        $answered = $session->current_question_id
            ? Answer::where('game_session_id', $session->id)
                ->where('question_id', $session->current_question_id)
                ->count()
            : 0;

        return response()->json([
            'online_count' => $onlineParticipants->count(),
            'participants_count' => $session->participants()->where('status', 'approved')->count(),
            'pending_count' => $session->participants()->where('status', 'pending')->count(),
            'pending_participants' => $session->participants()
                ->where('status', 'pending')
                ->orderBy('joined_at')
                ->get(['id', 'name'])
                ->values(),
            'answered_count' => $answered,
            'question' => $session->currentQuestion,
            'state' => $state,
            'session_status' => $session->status,
            'participants' => $onlineParticipants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'status' => $participant->status,
                    'online' => true,
                ];
            })->values(),
        ]);
    }

    // public function state(GameSession $session)
    // {
    //     $this->authorizeSession($session);

    //     $session->load('currentQuestion');

    //     $state = $session->current_question_id
    //         ? GameQuestionState::where('game_session_id', $session->id)
    //             ->where('question_id', $session->current_question_id)
    //             ->latest('id')->first()
    //         : null;

    //     $answered = $session->current_question_id
    //         ? Answer::where('game_session_id', $session->id)
    //             ->where('question_id', $session->current_question_id)->count()
    //         : 0;

    //     return response()->json([
    //         'session_status' => $session->status,
    //         'question' => $session->currentQuestion,
    //         'state' => $state,
    //         'participants_count' => $session->participants()->where('status', 'approved')->count(),
    //         'pending_count' => $session->participants()->where('status', 'pending')->count(),
    //         'pending_participants' => $session->participants()->where('status', 'pending')->orderBy('joined_at')->get(['id', 'name'])->values(),
    //         'answered_count' => $answered,
    //     ]);
    // }

    public function storeQuestion(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);

        $data = $request->validate([
            'content' => ['required', 'string'],
            'correct_answer' => ['required', 'string', 'max:1000'],
            'duration' => ['required', 'integer', 'min:1', 'max:3600'],
        ]);

        $number = ((int) $session->questions()->max('question_number')) + 1;

        $session->questions()->create([
            'question_number' => $number,
            ...$data,
        ]);

        return back()->with('success', 'Đã thêm câu hỏi.');
    }

    public function startQuestion(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);

        $question = $session->questions()->findOrFail($request->integer('question_id'));

        GameQuestionState::where('game_session_id', $session->id)
            ->where('status', 'running')
            ->update(['status' => 'ended', 'ended_at' => now()]);

        GameQuestionState::create([
            'game_session_id' => $session->id,
            'question_id' => $question->id,
            'status' => 'running',
            'started_at' => now(),
            'answer_revealed' => false,
        ]);

        $session->update([
            'current_question_id' => $question->id,
            'status' => 'running',
        ]);

        return response()->json(['ok' => true]);
    }

    public function endQuestion(GameSession $session)
    {
        $this->authorizeSession($session);

        $state = GameQuestionState::where('game_session_id', $session->id)
            ->where('question_id', $session->current_question_id)
            ->where('status', 'running')
            ->latest('id')->firstOrFail();

        $state->update(['status' => 'ended', 'ended_at' => now()]);
        $session->update(['status' => 'paused']);

        return response()->json(['ok' => true]);
    }

    public function revealAnswer(GameSession $session)
    {
        $this->authorizeSession($session);

        $state = GameQuestionState::where('game_session_id', $session->id)
            ->where('question_id', $session->current_question_id)
            ->latest('id')->firstOrFail();

        $state->update(['answer_revealed' => true]);

        return response()->json(['ok' => true]);
    }

    public function nextQuestion(GameSession $session)
    {
        $this->authorizeSession($session);

        $current = $session->currentQuestion;
        $next = $session->questions()
            ->when($current, fn($q) => $q->where('question_number', '>', $current->question_number))
            ->first();

        if (!$next) {
            $session->update(['status' => 'completed']);
            return response()->json(['ok' => true, 'completed' => true]);
        }

        $session->update([
            'current_question_id' => $next->id,
            'status' => 'waiting',
        ]);

        return response()->json(['ok' => true, 'completed' => false]);
    }

    public function approveParticipant(GameSession $session, \App\Models\Participant $participant)
    {
        $this->authorizeSession($session);
        abort_unless($participant->game_session_id === $session->id, 404);

        $participant->update(['status' => 'approved', 'last_seen_at' => now()]);

        return back()->with('success', $participant->name . ' đã được duyệt vào phòng.');
    }

    public function rejectParticipant(GameSession $session, \App\Models\Participant $participant)
    {
        $this->authorizeSession($session);
        abort_unless($participant->game_session_id === $session->id, 404);

        $participant->update(['status' => 'rejected']);

        return back()->with('success', $participant->name . ' đã bị từ chối.');
    }

    public function results(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);

        $session->load(['questions', 'participants']);

        $questionId = $request->integer('question_id') ?: null;

        $questions = $session->questions;
        if ($questionId) {
            $questions = $questions->where('id', $questionId)->values();
        }

        $answers = $this->dedupeAnswersByQuestionParticipant($session->id);

        $detailRows = collect();

        foreach ($questions as $question) {
            foreach ($session->participants as $participant) {
                $answer = $answers->get($question->id . ':' . $participant->id);

                $detailRows->push([
                    'question' => $question,
                    'participant' => $participant,
                    'answer' => $answer,
                    'answer_text' => $answer?->answer,
                    'answer_time_ms' => $answer?->answer_time_ms,
                    'is_correct' => $answer?->is_correct,
                    'status' => !$answer
                        ? 'unanswered'
                        : ($answer->is_correct ? 'correct' : 'wrong'),
                ]);
            }
        }

        $summary = $session->participants->map(function ($participant) use ($session, $answers) {
            $participantAnswers = $session->questions->map(
                fn($question) => $answers->get($question->id . ':' . $participant->id)
            );

            $correct = $participantAnswers->filter(fn($answer) => $answer?->is_correct)->count();
            $wrong = $participantAnswers->filter(fn($answer) => $answer && !$answer->is_correct)->count();
            $unanswered = $session->questions->count() - $correct - $wrong;

            $totalTime = $participantAnswers
                ->filter(fn($answer) => $answer && $answer->is_correct)
                ->sum(fn($answer) => (int) $answer->answer_time_ms);

            return [
                'participant' => $participant,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered,
                'total_time_ms' => $totalTime,
            ];
        })
            ->sort(function ($a, $b) {
                if ($a['correct'] !== $b['correct']) {
                    return $b['correct'] <=> $a['correct'];
                }

                return $a['total_time_ms'] <=> $b['total_time_ms'];
            })
            ->values();

        return view('teacher.sessions.results', compact(
            'session',
            'questions',
            'detailRows',
            'summary',
            'questionId'
        ));
    }

    public function export(GameSession $session)
    {
        $this->authorizeSession($session);

        $session->load(['questions', 'participants']);

        $answers = $this->dedupeAnswersByQuestionParticipant($session->id);

        $csv = "\xEF\xBB\xBF";
        $csv .= "Cau,Ho ten,Dap an,Dap an dung,Thoi gian (giay),Ket qua\n";

        foreach ($session->questions as $question) {
            foreach ($session->participants as $participant) {
                $answer = $answers->get($question->id . ':' . $participant->id);

                $result = !$answer
                    ? 'Chưa trả lời'
                    : ($answer->is_correct ? 'Đúng' : 'Sai');

                $time = $answer?->answer_time_ms !== null
                    ? number_format($answer->answer_time_ms / 1000, 1, '.', '')
                    : '';

                $csv .= implode(',', [
                    $question->question_number,
                    '"' . str_replace('"', '""', $participant->name) . '"',
                    '"' . str_replace('"', '""', $answer?->answer ?? '') . '"',
                    '"' . str_replace('"', '""', $question->correct_answer) . '"',
                    $time,
                    '"' . $result . '"',
                ]) . "\n";
            }
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="quiz-' . $session->join_code . '-results.csv"',
        ]);
    }

    private function dedupeAnswersByQuestionParticipant(int $sessionId)
    {
        return Answer::where('game_session_id', $sessionId)
            ->with(['participant', 'question'])
            ->orderByDesc('final_submitted_at')
            ->get()
            ->groupBy(fn($answer) => $answer->question_id . ':' . $answer->participant_id)
            ->map(fn($group) => $group->first());
    }

    private function authorizeSession(GameSession $session): void
    {
        abort_unless($session->created_by === auth()->id(), 403);
    }

    private function makeCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while (GameSession::where('join_code', $code)->exists());

        return $code;
    }
}
