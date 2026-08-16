<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\GameQuestionState;
use App\Models\GameSession;
use App\Models\Participant;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GameService
{
    public function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while (GameSession::where('join_code', $code)->exists());

        return $code;
    }

    public function start(GameSession $session): GameQuestionState
    {
        return DB::transaction(function () use ($session) {
            $session->refresh();

            if ($session->status === 'finished') {
                throw new RuntimeException('Cuộc thi đã hoàn thành.');
            }

            $question = $session->questions()->first();

            if (!$question) {
                throw new RuntimeException('Phiên chưa có câu hỏi.');
            }

            $current = $session->current_question_id
                ? $session->currentQuestion()->first()
                : null;

            if (!$current) {
                $current = $question;
                $session->update([
                    'current_question_id' => $current->id,
                ]);
            }

            $state = GameQuestionState::firstOrCreate(
                [
                    'game_session_id' => $session->id,
                    'question_id' => $current->id,
                ],
                [
                    'status' => 'waiting',
                ]
            );

            if ($state->status === 'running') {
                return $state;
            }

            if ($state->status === 'show_answer') {
                throw new RuntimeException('Câu hỏi đã hiển thị đáp án.');
            }

            $state->update([
                'status' => 'running',
                'started_at' => now(),
                'ended_at' => null,
            ]);

            $session->update([
                'status' => 'running',
            ]);

            $session->participants()->update([
                'status' => 'playing',
            ]);

            return $state->fresh();
        });
    }

    public function end(GameSession $session): GameQuestionState
    {
        return DB::transaction(function () use ($session) {
            $question = $session->currentQuestion()->first();

            if (!$question) {
                throw new RuntimeException('Không có câu hỏi hiện tại.');
            }

            $state = GameQuestionState::where([
                'game_session_id' => $session->id,
                'question_id' => $question->id,
            ])->lockForUpdate()->first();

            if (!$state || $state->status !== 'running') {
                throw new RuntimeException('Câu hỏi không ở trạng thái đang thi.');
            }

            $this->closeExpiredState($state, $question);

            $session->participants()->update([
                'status' => 'waiting',
            ]);

            return $state->fresh();
        });
    }

    public function showAnswer(GameSession $session): GameQuestionState
    {
        return DB::transaction(function () use ($session) {
            $question = $session->currentQuestion()->firstOrFail();

            $state = GameQuestionState::where([
                'game_session_id' => $session->id,
                'question_id' => $question->id,
            ])->lockForUpdate()->firstOrFail();

            if ($state->status === 'running') {
                $this->closeExpiredState($state, $question);
            }

            if ($state->status !== 'ended') {
                throw new RuntimeException('Câu hỏi chưa kết thúc.');
            }

            $this->gradeAnswers($session, $question);

            $state->update([
                'status' => 'show_answer',
            ]);

            return $state->fresh();
        });
    }

    public function next(GameSession $session): ?Question
    {
        return DB::transaction(function () use ($session) {
            $current = $session->currentQuestion()->first();

            if (!$current) {
                throw new RuntimeException('Không có câu hiện tại.');
            }

            $next = $session->questions()
                ->where('question_number', '>', $current->question_number)
                ->orderBy('question_number')
                ->first();

            if (!$next) {
                $session->update([
                    'status' => 'finished',
                ]);

                $session->participants()->update([
                    'status' => 'finished',
                ]);

                return null;
            }

            $session->update([
                'current_question_id' => $next->id,
                'status' => 'waiting',
            ]);

            GameQuestionState::updateOrCreate(
                [
                    'game_session_id' => $session->id,
                    'question_id' => $next->id,
                ],
                [
                    'status' => 'waiting',
                    'started_at' => null,
                    'ended_at' => null,
                ]
            );

            $session->participants()->update([
                'status' => 'waiting',
            ]);

            return $next;
        });
    }

    public function closeExpiredState(
        GameQuestionState $state,
        Question $question
    ): GameQuestionState {
        if (
            $state->status === 'running' &&
            $state->started_at &&
            $state->started_at->copy()
                ->addSeconds($question->duration)
                ->isPast()
        ) {
            $state->update([
                'status' => 'ended',
                'ended_at' => $state->started_at
                    ->copy()
                    ->addSeconds($question->duration),
            ]);

            return $state->fresh();
        }

        return $state;
    }

    public function gradeAnswers(
        GameSession $session,
        Question $question
    ): void {
        Answer::where('game_session_id', $session->id)
            ->where('question_id', $question->id)
            ->get()
            ->each(function (Answer $answer) use ($question) {
                $answer->update([
                    'is_correct' => $this->isCorrect(
                        (string) $answer->answer,
                        (string) $question->correct_answer
                    ),
                ]);
            });
    }

    public function isCorrect(string $answer, string $correct): bool
    {
        return mb_strtolower(trim($answer)) === mb_strtolower(trim($correct));
    }
}
