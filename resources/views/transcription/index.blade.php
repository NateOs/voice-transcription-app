<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Voice Transcription App') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <header class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Voice Transcription App</h1>
                <p class="text-gray-600">Record your voice and get real-time transcriptions</p>
            </header>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div id="app">
                    <!-- Recording Controls -->
                    <div class="text-center mb-6">
                        <button id="start-conversation" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                            Start New Conversation
                        </button>
                    </div>

                    <!-- Status Display -->
                    <div id="status" class="mb-4 p-4 bg-gray-50 rounded-lg hidden">
                        <p class="text-sm text-gray-600">Status: <span id="status-text">Ready</span></p>
                        <p class="text-sm text-gray-600">Thread ID: <span id="thread-id">-</span></p>
                    </div>

                    <!-- Recording Controls -->
                    <div id="recording-controls" class="text-center mb-6 hidden">
                        <button id="start-recording" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg mr-4 transition duration-200">
                            🎤 Start Recording
                        </button>
                        <button id="stop-recording" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200" disabled>
                            ⏹️ Stop Recording
                        </button>
                    </div>

                    <!-- Transcriptions Display -->
                    <div id="transcriptions" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Transcriptions</h3>
                        <div id="transcription-list" class="space-y-2">
                            <!-- Transcriptions will be added here -->
                        </div>
                    </div>

                    <!-- Debug/Response Area -->
                    <div id="debug" class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Debug Information</h4>
                        <pre id="debug-content" class="text-xs text-gray-600 whitespace-pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentThreadId = null;
        let mediaRecorder = null;
        let audioChunks = [];

        // DOM Elements
        const startConversationBtn = document.getElementById('start-conversation');
        const startRecordingBtn = document.getElementById('start-recording');
        const stopRecordingBtn = document.getElementById('stop-recording');
        const statusDiv = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        const threadIdSpan = document.getElementById('thread-id');
        const recordingControls = document.getElementById('recording-controls');
        const transcriptionList = document.getElementById('transcription-list');
        const debugContent = document.getElementById('debug-content');

        // Utility function to update debug info
        function updateDebug(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugContent.textContent += `[${timestamp}] ${message}\n`;
            debugContent.scrollTop = debugContent.scrollHeight;
        }

        // Start new conversation
        startConversationBtn.addEventListener('click', async () => {
            try {
                updateDebug('Starting new conversation...');
                
                const response = await fetch('/api/conversation/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                updateDebug(`Response: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    currentThreadId = data.thread_id;
                    threadIdSpan.textContent = currentThreadId;
                    statusText.textContent = 'Conversation started';
                    statusDiv.classList.remove('hidden');
                    recordingControls.classList.remove('hidden');
                    startConversationBtn.textContent = 'Start New Conversation';
                } else {
                    updateDebug('Failed to start conversation');
                }
            } catch (error) {
                updateDebug(`Error: ${error.message}`);
            }
        });

        // Test API endpoint
        updateDebug('Voice Transcription App loaded');
        updateDebug('Click "Start New Conversation" to begin');
    </script>
</body>
</html>