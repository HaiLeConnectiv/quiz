<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\GameQuestionState;
use App\Models\GameSession;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function showJoin(string $code)
    {
        $session = GameSession::where('join_code', strtoupper($code))->firstOrFail();

        return view('student.join', compact('session'));
    }

    public function join(Request $request, string $code)
    {
        $session = GameSession::where('join_code', strtoupper($code))->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);

        if ($session->password_enabled &&
            !hash_equals((string) $session->password, (string) ($data['password'] ?? ''))) {
            return back()
                ->withErrors(['password' => 'Mật khẩu phòng không đúng.'])
                ->withInput();
        }

        $participant = Participant::create([
            'game_session_id' => $session->id,
            'name' => $data['name'],
            'token' => Str::random(64),
            'status' => 'pending',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        session(['student_token_' . $session->id => $participant->token]);

        return redirect()->route('student.play', ['code' => $session->join_code]);
    }

    public function play(string $code)
    {
        $session = GameSession::where('join_code', strtoupper($code))->firstOrFail();
        $participant = $this->participant($session);

        if ($participant->status === 'pending') {
            return view('student.pending', compact('session', 'participant'));
        }

        abort_if($participant->status === 'rejected', 403, 'Bạn chưa được giáo viên duyệt vào phòng.');

        return view('student.play', compact('session', 'participant'));
    }

    public function state(string $code)
    {
        $session = GameSession::where('join_code', strtoupper($code))->firstOrFail();
        $participant = $this->participant($session);

        $participant->update(['last_seen_at' => now()]);

        if ($participant->status === 'pending') {
            return response()->json([
                'approved' => false,
                'status' => 'pending',
                'message' => 'Đang chờ giáo viên duyệt vào phòng.',
            ]);
        }

        if ($participant->status === 'rejected') {
            return response()->json([
                'approved' => false,
                'status' => 'rejected',
                'message' => 'Giáo viên đã từ chối bạn vào phòng.',
            ], 403);
        }

        $session->load('currentQuestion');

        $state = $session->current_question_id
            ? GameQuestionState::where('game_session_id', $session->id)
                ->where('question_id', $session->current_question_id)
                ->latest('id')
                ->first()
            : null;

        $answer = $session->current_question_id
            ? Answer::where('game_session_id', $session->id)
                ->where('question_id', $session->current_question_id)
                ->where('participant_id', $participant->id)
                ->first()
            : null;

        return response()->json([
            'session' => [
                'name' => $session->name,
                'status' => $session->status,
            ],
            'participant' => [
                'name' => $participant->name,
            ],
            'question' => $session->currentQuestion,
            'state' => $state,
            'answer' => $answer,
            'show_question' => true,
        ]);
    }

    public function answer(Request $request, string $code)
    {
        $session = GameSession::where('join_code', strtoupper($code))->firstOrFail();
        $participant = $this->participant($session);
        abort_if($participant->status !== 'approved', 403, 'Bạn chưa được giáo viên duyệt vào phòng.');

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $question = $session->questions()->findOrFail($data['question_id']);

        $state = GameQuestionState::where('game_session_id', $session->id)
            ->where('question_id', $question->id)
            ->where('status', 'running')
            ->latest('id')
            ->first();

        if (!$state || !$state->started_at) {
            return response()->json(['message' => 'Câu hỏi chưa mở.'], 422);
        }

        $elapsed = now()->diffInMilliseconds($state->started_at);

        if ($elapsed > ($question->duration * 1000)) {
            return response()->json(['message' => 'Đã hết giờ.'], 422);
        }

        $answer = Answer::updateOrCreate(
            [
                'game_session_id' => $session->id,
                'question_id' => $question->id,
                'participant_id' => $participant->id,
            ],
            [
                'answer' => $data['answer'],
                'final_submitted_at' => now(),
                'answer_time_ms' => $elapsed,
                'is_correct' =>
                    mb_strtolower(trim($data['answer'])) ===
                    mb_strtolower(trim($question->correct_answer)),
            ]
        );

        if (!$answer->first_submitted_at) {
            $answer->first_submitted_at = now();
            $answer->save();
        }

        return response()->json([
            'ok' => true,
            'answer_time_ms' => $elapsed,
        ]);
    }

    private function participant(GameSession $session): Participant
    {
        $token = session('student_token_' . $session->id);

        abort_unless($token, 403);

        return Participant::where('game_session_id', $session->id)
            ->where('token', $token)
            ->firstOrFail();
    }
}
