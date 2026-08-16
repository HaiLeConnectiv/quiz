<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Teacher\GameSessionController;
use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/sessions', [GameSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [GameSessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [GameSessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}/edit', [GameSessionController::class, 'edit'])->name('sessions.edit');
    Route::put('/sessions/{session}', [GameSessionController::class, 'update'])->name('sessions.update');
    Route::get('/sessions/{session}/control', [GameSessionController::class, 'control'])->name('sessions.control');
    Route::get('/sessions/{session}/state', [GameSessionController::class, 'state'])->name('sessions.state');
    Route::post('/sessions/{session}/participants/{participant}/approve', [GameSessionController::class, 'approveParticipant'])->name('sessions.participants.approve');
    Route::post('/sessions/{session}/participants/{participant}/reject', [GameSessionController::class, 'rejectParticipant'])->name('sessions.participants.reject');
    Route::post('/sessions/{session}/questions', [GameSessionController::class, 'storeQuestion'])->name('sessions.questions.store');
    Route::post('/sessions/{session}/start-question', [GameSessionController::class, 'startQuestion'])->name('sessions.start');
    Route::post('/sessions/{session}/end-question', [GameSessionController::class, 'endQuestion'])->name('sessions.end');
    Route::post('/sessions/{session}/reveal-answer', [GameSessionController::class, 'revealAnswer'])->name('sessions.reveal');
    Route::post('/sessions/{session}/next-question', [GameSessionController::class, 'nextQuestion'])->name('sessions.next');
    Route::get('/sessions/{session}/results', [GameSessionController::class, 'results'])->name('sessions.results');
    Route::get('/sessions/{session}/export', [GameSessionController::class, 'export'])->name('sessions.export');
});

Route::get('/join/{code}', [StudentController::class, 'showJoin'])->name('student.join');
Route::post('/join/{code}', [StudentController::class, 'join'])->name('student.join.store');
Route::get('/join/{code}/play', [StudentController::class, 'play'])->name('student.play');
Route::get('/join/{code}/state', [StudentController::class, 'state'])->name('student.state');
Route::post('/join/{code}/answer', [StudentController::class, 'answer'])->name('student.answer');
