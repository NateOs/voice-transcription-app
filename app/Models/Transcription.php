<?php
// File: /voice-transcription-app/app/Models/Transcription.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transcription extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'audio_file_path',
        'transcription',
        'duration',
        'whisper_response',
        'status',
        'error_message',
    ];

    protected $casts = [
        'duration' => 'decimal:2',
        'whisper_response' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getAudioUrlAttribute(): string
    {
        return asset('storage/' . $this->audio_file_path);
    }
}