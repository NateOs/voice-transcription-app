<?php
// File: /voice-transcription-app/app/Http/Controllers/ConversationController.php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Transcription;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConversationController extends Controller
{
	public function __construct(
		private WhisperService $whisperService
	) {}

	public function index(): View
	{
		return view('transcription.index');
	}

	public function start(): JsonResponse
	{
		try {
			$threadId = Str::uuid()->toString();
			
			$conversation = Conversation::create([
				'thread_id' => $threadId,
				'started_at' => now(),
				'last_activity_at' => now(),
			]);

			return response()->json([
				'success' => true,
				'thread_id' => $threadId,
				'conversation_id' => $conversation->id,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Failed to start conversation: ' . $e->getMessage(),
			], 500);
		}
	}

	public function uploadAudio(Request $request): JsonResponse
	{
		$request->validate([
			'audio' => 'required|file|mimes:webm,wav,mp3,m4a|max:10240', // 10MB max
			'thread_id' => 'required|string',
			'duration' => 'required|numeric|min:0.1',
		]);

		try {
			$conversation = Conversation::where('thread_id', $request->thread_id)->firstOrFail();
			
			// Store audio file
			$audioPath = $this->whisperService->storeAudioFile(
				$request->file('audio'),
				$request->thread_id
			);

			// Create transcription record
			$transcription = Transcription::create([
				'conversation_id' => $conversation->id,
				'audio_file_path' => $audioPath,
				'duration' => $request->duration,
				'transcription' => '',
				'status' => 'processing',
			]);

			// Process transcription - pass the uploaded file directly
			$result = $this->whisperService->transcribeAudio($request->file('audio'));

			if ($result['success']) {
				$transcription->update([
					'transcription' => $result['transcription'],
					'whisper_response' => $result['response'],
					'status' => 'completed',
				]);

				$conversation->updateLastActivity();

				return response()->json([
					'success' => true,
					'transcription' => $result['transcription'],
					'transcription_id' => $transcription->id,
				]);
			} else {
				$transcription->update([
					'status' => 'failed',
					'error_message' => $result['error'],
				]);

				return response()->json([
					'success' => false,
					'error' => 'Transcription failed: ' . $result['error'],
				], 500);
			}
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server error: ' . $e->getMessage(),
			], 500);
		}
	}

	public function getConversation(string $threadId): JsonResponse
	{
		$conversation = Conversation::with(['transcriptions' => function ($query) {
			$query->where('status', 'completed')->orderBy('created_at');
		}])->where('thread_id', $threadId)->first();

		if (!$conversation) {
			return response()->json([
				'success' => false,
				'error' => 'Conversation not found',
			], 404);
		}

		return response()->json([
			'success' => true,
			'conversation' => $conversation,
			'transcriptions' => $conversation->transcriptions,
			'full_transcription' => $conversation->full_transcription,
		]);
	}
}