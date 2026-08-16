<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $fillable = [
        'name','join_code','password','password_enabled','status',
        'current_question_id','created_by'
    ];

    protected $casts = [
        'password_enabled' => 'boolean',
    ];

    protected $hidden = ['password'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('question_number');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function questionStates(): HasMany
    {
        return $this->hasMany(GameQuestionState::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function currentQuestion()
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }
}
