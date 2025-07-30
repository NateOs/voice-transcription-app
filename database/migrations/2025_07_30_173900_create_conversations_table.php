<?php
// File: /voice-transcription-app/database/migrations/2024_01_01_000001_create_conversations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('thread_id')->unique()->index();
            $table->string('title')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};