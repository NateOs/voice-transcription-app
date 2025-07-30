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
    
    <!-- TailwindCSS via CDN (temporary) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Instrument Sans', sans-serif;
        }
        
        .recording-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
        
        .transcription-item {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .floating-orb {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .orb-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            right: 10%;
            animation: float 6s ease-in-out infinite;
        }
        
        .orb-2 {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 15%;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .button-recording {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .button-recording:hover {
            background: linear-gradient(45deg, #dc2626, #b91c1c);
        }
         body {
        overflow: hidden;
    }
    .overflow-y-auto {
        height: 100vh;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }
    </style>
</head>
<body class="min-h-screen relative overflow-hidden">
    <!-- Floating Orbs for Design -->
    <div class="floating-orb orb-1"></div>
    <div class="floating-orb orb-2"></div>
    
    <div class="relative z-10 min-h-screen flex">
        <!-- Left Column -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="text-center pt-16 pb-8">
                <h1 class="text-5xl font-bold text-white mb-4">Welcome to Darli</h1>
                <p class="text-white/80 text-lg">Your AI-powered voice transcription assistant</p>
            </header>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col items-center justify-center px-4">
                <!-- Recording Button -->
                <div class="text-center mb-8">
                    <button id="main-button" class="bg-black hover:bg-gray-800 text-white font-semibold py-4 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-white/30 min-w-[200px]">
                        Start Transcription
                    </button>
                    <div id="status-text" class="mt-2 text-white/80"></div>

                    <!-- Recording Status -->
                    <div id="recording-status" class="mt-4 text-white/80 hidden">
                        <div class="flex items-center justify-center space-x-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full recording-pulse"></div>
                            <span>Recording... <span id="timer">00:00</span></span>
                        </div>
                    </div>
                </div>

                <!-- Waveform Visualization -->
                <div id="waveform" class="w-full max-w-2xl h-32"></div>
            </div>
        </div>

        <!-- Right Column - Transcriptions -->
        <div class="w-1/3 bg-white/10 backdrop-blur-lg p-6 overflow-y-auto">
            <h3 class="text-xl font-semibold text-white mb-4">Transcript</h3>
            <div id="transcription-list" class="space-y-4">
                <!-- Transcriptions will be added here -->
            </div>
        </div>
    </div>

    <!-- Debug Panel (Hidden by default) -->
    <div id="debug-panel" class="fixed bottom-4 right-4 bg-black/80 text-white p-4 rounded-lg max-w-md hidden">
        <h4 class="font-semibold mb-2">Debug Info</h4>
        <pre id="debug-content" class="text-xs whitespace-pre-wrap max-h-32 overflow-y-auto"></pre>
    </div>
    
    <!-- Toggle Debug Button -->
    <button id="toggle-debug" class="fixed bottom-4 left-4 bg-white/20 text-white p-2 rounded-full hover:bg-white/30 transition-colors">
        🐛
    </button>
    </div>
    <script>
        let currentThreadId = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let recordingStartTime = null;
        let timerInterval = null;
        let isRecording = false;
        let conversationStarted = false;

        // DOM Elements
        const mainButton = document.getElementById('main-button');
const recordingStatus = document.getElementById('recording-status');
const statusText = document.getElementById('status-text');  // Add this line
const timerSpan = document.getElementById('timer');
        const transcriptionList = document.getElementById('transcription-list');
        const transcriptionsContainer = document.getElementById('transcriptions-container');
        const debugContent = document.getElementById('debug-content');
        const debugPanel = document.getElementById('debug-panel');
        const toggleDebugBtn = document.getElementById('toggle-debug');

        // Utility function to update debug info
        function updateDebug(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugContent.textContent += `[${timestamp}] ${message}\n`;
            debugContent.scrollTop = debugContent.scrollHeight;
        }

        // Format time for timer display
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Update recording timer
        function updateTimer() {
            if (recordingStartTime) {
                const elapsed = (Date.now() - recordingStartTime) / 1000;
                timerSpan.textContent = formatTime(elapsed);
            }
        }

        // Add transcription to the list
       function addTranscription(text, timestamp) {
    const transcriptionDiv = document.createElement('div');
    transcriptionDiv.className = 'bg-white/20 backdrop-blur-sm rounded-lg border border-white/30 p-4 mb-4';
    transcriptionDiv.innerHTML = `
        <div class="text-sm text-white/70 mb-2">${timestamp}</div>
        <div class="text-white">${text}</div>
    `;
    transcriptionList.prepend(transcriptionDiv); // Add new transcriptions to the top
}

        // Start new conversation
        async function startConversation() {
            try {
                updateDebug('Starting new conversation...');
                statusText.textContent = 'Starting conversation...';
                
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
                    conversationStarted = true;
                    statusText.textContent = 'Ready to record';
                    mainButton.textContent = '🎤 Start Recording';
                    
                    // Clear previous transcriptions
                    transcriptionList.innerHTML = '';
                    transcriptionsContainer.classList.add('hidden');
                    
                    updateDebug('Conversation started successfully');
                } else {
                    updateDebug('Failed to start conversation');
                    statusText.textContent = 'Failed to start conversation';
                }
            } catch (error) {
                updateDebug(`Error: ${error.message}`);
                statusText.textContent = 'Error starting conversation';
            }
        }

        // Start recording
      // Start recording
async function startRecording() {
    try {
        updateDebug('Requesting microphone access...');
        statusText.textContent = 'Requesting microphone access...';
        
        const stream = await navigator.mediaDevices.getUserMedia({ 
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                sampleRate: 44100
            } 
        });
        
        updateDebug('Microphone access granted');
        
        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: 'audio/webm;codecs=opus'
        });
        
        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };
        
        mediaRecorder.onstop = async () => {
            updateDebug('Recording stopped, processing audio...');
            statusText.textContent = 'Processing audio...';
            
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const duration = (Date.now() - recordingStartTime) / 1000;
            
            // Upload and transcribe
            await uploadAudio(audioBlob, duration);
            
            // Stop all tracks to release microphone
            stream.getTracks().forEach(track => track.stop());
        };
        
        // Start recording
        mediaRecorder.start();
        recordingStartTime = Date.now();
        isRecording = true;
        
        // Update UI
        mainButton.textContent = '⏹️ Stop Recording';
        mainButton.classList.add('button-recording', 'recording-pulse');
        recordingStatus.classList.remove('hidden');
        statusText.textContent = 'Recording in progress...';
        
        // Start the timer
        timerInterval = setInterval(updateTimer, 1000);

        updateDebug('Recording started');
    } catch (error) {
        updateDebug(`Error starting recording: ${error.message}`);
        statusText.textContent = 'Error starting recording';
        console.error('Error starting recording:', error);
    }
}

// Stop recording
async function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        clearInterval(timerInterval);

        // Update UI
        mainButton.textContent = '🎤 Start Recording';
        mainButton.classList.remove('button-recording', 'recording-pulse');
        recordingStatus.classList.add('hidden');
        statusText.textContent = 'Processing...';

        updateDebug('Recording stopped');
    }
}

// Upload audio
async function uploadAudio(audioBlob, duration) {
    try {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');
        formData.append('thread_id', currentThreadId);
        formData.append('duration', duration);

        const response = await fetch('/api/conversation/upload-audio', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();
        updateDebug(`Upload response: ${JSON.stringify(data)}`);

        if (data.success) {
            addTranscription(data.transcription, new Date().toLocaleTimeString());
            statusText.textContent = 'Transcription complete';
        } else {
            statusText.textContent = 'Failed to process audio';
        }
    } catch (error) {
        updateDebug(`Error uploading audio: ${error.message}`);
        statusText.textContent = 'Error processing audio';
    }
}

// Event Listeners
mainButton.addEventListener('click', async () => {
    if (!conversationStarted) {
        await startConversation();
    } else if (isRecording) {
        await stopRecording();
    } else {
        await startRecording();
    }
});

toggleDebugBtn.addEventListener('click', () => {
    debugPanel.classList.toggle('hidden');
});

// Initialize
startConversation();
</script>
</body>
</html>