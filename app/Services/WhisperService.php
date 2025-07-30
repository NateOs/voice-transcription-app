<?php
// File: /voice-transcription-app/app/Services/WhisperService.php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class WhisperService
{
	public function transcribeAudio(string $audioFilePath): array
	{
		try {
			$response = OpenAI::audio()->transcriptions()->create([
				'model' => 'whisper-1',
				'file' => fopen(storage_path('app/' . $audioFilePath), 'r'),
				'response_format' => 'verbose_json',
				'timestamp_granularities' => ['word'],
			]);

			return [
				'success' => true,
				'transcription' => $response->text,
				'response' => $response->toArray(),
			];
		} catch (Exception $e) {
			Log::error('Whisper API Error: ' . $e->getMessage());
			
			return [
				'success' => false,
				'error' => $e->getMessage(),
				'transcription' => null,
				'response' => null,
			];
		}
	}

	public function storeAudioFile(UploadedFile $audioFile, string $conversationThreadId): string
	{
		$filename = $conversationThreadId . '_' . time() . '_' . uniqid() . '.webm';
		$path = $audioFile->storeAs('audio/' . $conversationThreadId, $filename);
		
		return $path;
	}
}