<?php

use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::prefix('conversation')->group(function () {
    Route::post('/start', [ConversationController::class, 'start']);
    Route::post('/upload-audio', [ConversationController::class, 'uploadAudio']);
    Route::get('/{threadId}', [ConversationController::class, 'getConversation']);
});
