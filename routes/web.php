<?php
// File: /voice-transcription-app/routes/web.php

use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ConversationController::class, 'index'])->name('home');

Route::prefix('api')->group(function () {
    Route::post('/conversation/start', [ConversationController::class, 'start']);
    Route::post('/conversation/upload-audio', [ConversationController::class, 'uploadAudio']);
    Route::get('/conversation/{threadId}', [ConversationController::class, 'getConversation']);
});