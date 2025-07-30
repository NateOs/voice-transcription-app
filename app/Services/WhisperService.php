<?php
// File: /home/nathansodja/voice-transcription-app/app/Services/WhisperService.php

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
            Log::info('Starting transcription process');
            Log::info('Original filename: ' . $audioFile->getClientOriginalName());
            Log::info('File size: ' . $audioFile->getSize());
            Log::info('MIME type: ' . $audioFile->getMimeType());

            // Check storage directory permissions
            $storageAppPath = storage_path('app');
            $audioDir = storage_path('app/audio');
            
            Log::info('Storage app path: ' . $storageAppPath);
            Log::info('Storage app exists: ' . (is_dir($storageAppPath) ? 'yes' : 'no'));
            Log::info('Storage app writable: ' . (is_writable($storageAppPath) ? 'yes' : 'no'));
            Log::info('Storage app permissions: ' . substr(sprintf('%o', fileperms($storageAppPath)), -4));
            
            // Ensure audio directory exists with proper permissions
            if (!is_dir($audioDir)) {
                $created = mkdir($audioDir, 0775, true);
                Log::info('Created audio directory: ' . ($created ? 'success' : 'failed'));
                if ($created) {
                    chmod($audioDir, 0775);
                }
            }
            
            Log::info('Audio dir exists: ' . (is_dir($audioDir) ? 'yes' : 'no'));
            Log::info('Audio dir writable: ' . (is_writable($audioDir) ? 'yes' : 'no'));
            if (is_dir($audioDir)) {
                Log::info('Audio dir permissions: ' . substr(sprintf('%o', fileperms($audioDir)), -4));
            }

            // Generate unique filename
            $filename = 'temp_' . time() . '_' . uniqid() . '.' . $audioFile->getClientOriginalExtension();
            Log::info('Generated filename: ' . $filename);

            // Try to store the file with more detailed error handling
            try {
                Log::info('Attempting to store file...');
                $audioPath = $audioFile->storeAs('audio', $filename, 'local');
                Log::info('Storage method returned path: ' . $audioPath);
                
                // Immediately check if file was actually created
                $fullPath = storage_path('app/' . $audioPath);
                Log::info('Expected full path: ' . $fullPath);
                
                // Wait a moment and check again (sometimes there's a delay)
                usleep(100000); // 0.1 second
                
                if (file_exists($fullPath)) {
                    Log::info('File successfully created and exists');
                    Log::info('File size on disk: ' . filesize($fullPath));
                    Log::info('File permissions: ' . substr(sprintf('%o', fileperms($fullPath)), -4));
                } else {
                    Log::error('File was not created despite storage method success');
                    
                    // Try alternative storage method
                    Log::info('Trying alternative storage method...');
                    $alternativePath = $audioDir . '/' . $filename;
                    $moved = move_uploaded_file($audioFile->getPathname(), $alternativePath);
                    
                    if ($moved && file_exists($alternativePath)) {
                        Log::info('Alternative storage method succeeded');
                        $audioPath = 'audio/' . $filename;
                        $fullPath = $alternativePath;
                    } else {
                        Log::error('Alternative storage method also failed');
                        return [
                            'success' => false,
                            'error' => 'Failed to store audio file - permission issue likely',
                        ];
                    }
                }
                
            } catch (\Exception $storeException) {
                Log::error('Exception during file storage: ' . $storeException->getMessage());
                return [
                    'success' => false,
                    'error' => 'Failed to store audio file: ' . $storeException->getMessage(),
                ];
            }

            // Use HTTP client to make the API call directly
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->attach(
                'file', 
                fopen($fullPath, 'r'), 
                $filename
            )->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
            ]);

            Log::info('API Response Status: ' . $response->status());
            Log::info('API Response Body: ' . $response->body());

            // Clean up temporary file after API call
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('Cleaned up temporary file');
            }

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