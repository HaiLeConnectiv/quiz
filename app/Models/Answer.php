<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = [
        'game_session_id',
        'question_id',
        'participant_id',
        'answer',
        'first_submitted_at',
        'final_submitted_at',
        'answer_time_ms',
        'is_correct',
    ];

    protected $casts = [
        'first_submitted_at' => 'datetime',
        'final_submitted_at' => 'datetime',
        'is_correct' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
}
