<?php
// File: /voice-transcription-app/app/Services/WhisperService.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhisperService
{
    public function transcribeAudio(UploadedFile $audioFile): array
    {
        try {
            // Ensure temp directory exists
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }

            // Create a temporary file path
            $tempPath = $audioFile->store('temp', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            Log::info('Temp file created at: ' . $fullPath);
            Log::info('File exists: ' . (file_exists($fullPath) ? 'yes' : 'no'));
            Log::info('File size: ' . (file_exists($fullPath) ? filesize($fullPath) : 'N/A'));

            // Verify file exists
            if (!file_exists($fullPath)) {
                Log::error('Temporary file not found: ' . $fullPath);
                return [
                    'success' => false,
                    'error' => 'Temporary file not found at: ' . $fullPath,
                ];
            }

            // Use HTTP client to make the API call directly
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->attach(
                'file', 
                fopen($fullPath, 'r'), 
                basename($fullPath)
            )->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'gpt-4o-transcribe',
            ]);

            Log::info('API Response Status: ' . $response->status());
            Log::info('API Response Body: ' . $response->body());

            // Clean up temporary file after API call
            // Storage::disk('local')->delete($tempPath);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'transcription' => $data['text'] ?? '',
                    'response' => $data,
                ];
            } else {
                Log::error('OpenAI API error: ' . $response->body());
                
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status() . ' - ' . $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Whisper transcription failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function storeAudioFile(UploadedFile $audioFile, string $threadId): string
    {
        $filename = $threadId . '_' . time() . '.' . $audioFile->getClientOriginalExtension();
        return $audioFile->storeAs('audio', $filename, 'local');
    }
}