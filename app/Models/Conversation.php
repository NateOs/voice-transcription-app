<?php
// File: /voice-transcription-app/app/Models/Conversation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'title',
        'started_at',
        'last_activity_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function transcriptions(): HasMany
    {
        return $this->hasMany(Transcription::class);
    }

    public function getFullTranscriptionAttribute(): string
    {
        return $this->transcriptions()
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->pluck('transcription')
            ->implode(' ');
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}