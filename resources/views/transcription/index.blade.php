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

        /* WaveSurfer Styling */
        #waveform {
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

    <script type="module">
        import WaveSurfer from 'https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/7.7.2/wavesurfer.esm.min.js'
        import RecordPlugin from 'https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/7.7.2/plugins/record.esm.min.js'

        // Application State
        let currentThreadId = null;
        let conversationStarted = false;
        let segmentCounter = 0;
        let selectedDeviceId = null;

        // WaveSurfer components
        let wavesurfer = null;
        let record = null;
        let isRecording = false;

        // Silence detection
        let mediaRecorder = null;
        let currentAudioChunks = [];
        let audioContext = null;
        let analyser = null;
        let silenceTimer = null;
        let silenceThreshold = 0.005; // Lower threshold (was 0.01)
        let silenceTimeout = 1500; // Shorter timeout (was 2000ms)
        let minAudioDuration = 500; // Minimum audio duration before processing
        let minSilenceDuration = 800; // Minimum silence before processing
        let lastSoundTime = 0;
        let recordingStartTime = null;
        let timerInterval = null;

        // DOM Elements
        const mainButton = document.getElementById('main-button');
        const recordingStatus = document.getElementById('recording-status');
        const statusText = document.getElementById('status-text');
        const timerSpan = document.getElementById('timer');
        const transcriptionList = document.getElementById('transcription-list');
        const debugContent = document.getElementById('debug-content');
        const debugPanel = document.getElementById('debug-panel');
        const toggleDebugBtn = document.getElementById('toggle-debug');

        // Utility Functions
        function updateDebug(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugContent.textContent += `[${timestamp}] ${message}\n`;
            debugContent.scrollTop = debugContent.scrollHeight;
        }

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function updateTimer() {
            if (recordingStartTime) {
                const elapsed = (Date.now() - recordingStartTime) / 1000;
                timerSpan.textContent = formatTime(elapsed);
                mainButton.textContent = `⏹️ Stop Recording (${formatTime(elapsed)})`;
            }
        }

        // WaveSurfer Setup
        const setupWaveSurfer = async () => {
            if (wavesurfer) {
                wavesurfer.destroy();
            }

            wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'rgba(255, 255, 255, 0.8)',
                progressColor: 'rgba(255, 255, 255, 1)',
                height: 128,
                barWidth: 3,
                barGap: 1,
                barRadius: 2
            });

            record = wavesurfer.registerPlugin(
                RecordPlugin.create({
                    renderRecordedAudio: false,
                    scrollingWaveform: true,
                    continuousWaveform: true,
                })
            );
        };

        const loadMicrophones = async () => {
            try {
                const devices = await RecordPlugin.getAvailableAudioDevices();
                if (devices.length > 0) {
                    selectedDeviceId = devices[0].deviceId;
                    updateDebug(`Auto-selected microphone: ${devices[0].label || 'Default'}`);
                }
            } catch (error) {
                console.error('Error loading microphones:', error);
                updateDebug('Error loading microphones');
            }
        };

        // Silence Detection
        const setupSilenceDetection = async (stream) => {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioContext.createMediaStreamSource(stream);
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 2048;
            source.connect(analyser);

            detectSilence();
            updateDebug('Silence detection started');
        };

        const detectSilence = () => {
            if (!isRecording) return;

            try {
                const bufferLength = analyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);
                analyser.getByteFrequencyData(dataArray);

                // Calculate RMS for volume level
                let sum = 0;
                for (let i = 0; i < bufferLength; i++) {
                    sum += dataArray[i] * dataArray[i];
                }
                const rms = Math.sqrt(sum / bufferLength) / 255;

                const currentTime = Date.now();
                
                // Update visual feedback
                try {
                    const levelElement = document.getElementById('level-value');
                    const statusElement = document.getElementById('audio-status');
                    const segmentElement = document.getElementById('segment-count');
                    
                    if (levelElement) levelElement.textContent = rms.toFixed(4);
                    if (statusElement) statusElement.textContent = rms > silenceThreshold ? 'Sound' : 'Silence';
                    if (segmentElement) segmentElement.textContent = segmentCounter;
                } catch (e) {
                    // Ignore DOM update errors
                }
                
                if (rms > silenceThreshold) {
                    lastSoundTime = currentTime;
                    
                    // Clear any existing silence timer since we detected sound
                    if (silenceTimer) {
                        clearTimeout(silenceTimer);
                        silenceTimer = null;
                        updateDebug('Sound detected, clearing silence timer');
                    }
                } else {
                    // We're in silence - check if we should set a timer
                    // Remove the currentAudioChunks.length > 0 condition that was causing the issue
                    if (!silenceTimer && lastSoundTime > 0) {
                        const silenceDuration = currentTime - lastSoundTime;
                        if (silenceDuration >= minSilenceDuration) {
                            updateDebug(`Setting silence timer (${silenceDuration}ms of silence detected, chunks: ${currentAudioChunks.length})`);
                            silenceTimer = setTimeout(() => {
                                updateDebug('Silence timer triggered, processing audio...');
                                processSilenceDetected();
                                silenceTimer = null;
                            }, silenceTimeout);
                        }
                    }
                }

                // More frequent debug for testing
                if (Math.random() < 0.05) { // 5% of frames
                    updateDebug(`RMS: ${rms.toFixed(4)}, Silence: ${rms <= silenceThreshold}, Timer: ${silenceTimer ? 'Set' : 'None'}, Chunks: ${currentAudioChunks.length}, LastSound: ${lastSoundTime > 0 ? (currentTime - lastSoundTime) + 'ms ago' : 'never'}`);
                }

            } catch (error) {
                updateDebug(`Error in detectSilence: ${error.message}`);
            }

            if (isRecording) {
                requestAnimationFrame(detectSilence);
            }
        };

        const processSilenceDetected = async () => {
            if (!isRecording || currentAudioChunks.length === 0) {
                updateDebug(`Silence detected but not processing: recording=${isRecording}, chunks=${currentAudioChunks.length}`);
                return;
            }

            const audioDuration = Date.now() - recordingStartTime;
            if (audioDuration < minAudioDuration) {
                updateDebug(`Audio too short (${audioDuration}ms), skipping...`);
                return;
            }

            updateDebug(`Processing ${currentAudioChunks.length} audio chunks (${audioDuration}ms duration)...`);
            
            const audioBlob = new Blob(currentAudioChunks, { type: 'audio/webm' });
            const audioSize = audioBlob.size;
            
            // Clear chunks before upload to prevent reprocessing
            currentAudioChunks = [];
            segmentCounter++;
            
            // Reset recording start time for next segment
            recordingStartTime = Date.now();
            lastSoundTime = Date.now(); // Reset to current time
            
            updateDebug(`Uploading audio blob: ${audioSize} bytes, segment ${segmentCounter}`);
            
            try {
                await uploadAudio(audioBlob, segmentCounter);
                updateDebug(`Successfully uploaded segment ${segmentCounter}`);
            } catch (error) {
                updateDebug(`Failed to upload segment ${segmentCounter}: ${error.message}`);
            }
        };

        // Recording Functions
        const startRecording = async () => {
            try {
                if (!selectedDeviceId) {
                    updateDebug('No microphone available');
                    statusText.textContent = 'No microphone available';
                    return;
                }

                mainButton.disabled = true;
                statusText.textContent = 'Starting recording...';

                // Start WaveSurfer recording
                await record.startRecording({ deviceId: selectedDeviceId });
                
                // Get media stream for silence detection
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: { 
                        deviceId: { exact: selectedDeviceId },
                        echoCancellation: true,
                        noiseSuppression: true,
                        sampleRate: 44100
                    }
                });

                // Setup MediaRecorder for audio chunks
                mediaRecorder = new MediaRecorder(stream, {
                    mimeType: 'audio/webm;codecs=opus'
                });
                
                mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        currentAudioChunks.push(event.data);
                    }
                };
                
                mediaRecorder.start(100); // Collect data every 100ms

                // Setup silence detection
                await setupSilenceDetection(stream);

                isRecording = true;
                recordingStartTime = Date.now();
                lastSoundTime = Date.now();
                
                mainButton.textContent = '⏹️ Stop Recording';
                mainButton.classList.add('button-recording', 'recording-pulse');
                mainButton.disabled = false;
                recordingStatus.classList.remove('hidden');
                statusText.textContent = 'Recording in progress...';
                
                timerInterval = setInterval(updateTimer, 1000);
                updateDebug('Recording started');

            } catch (error) {
                console.error('Recording error:', error);
                updateDebug(`Recording error: ${error.message}`);
                statusText.textContent = 'Error starting recording';
                mainButton.disabled = false;
            }
        };

        const stopRecording = async () => {
            try {
                isRecording = false;
                
                if (silenceTimer) {
                    clearTimeout(silenceTimer);
                    silenceTimer = null;
                }

                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }

                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }

                if (audioContext) {
                    await audioContext.close();
                    audioContext = null;
                }

                record.stopRecording();
                
                // Process any remaining audio
                if (currentAudioChunks.length > 0) {
                    const finalBlob = new Blob(currentAudioChunks, { type: 'audio/webm' });
                    segmentCounter++;
                    await uploadAudio(finalBlob, segmentCounter);
                    currentAudioChunks = [];
                }

                mainButton.textContent = '🎤 Start Recording';
                mainButton.classList.remove('button-recording', 'recording-pulse');
                recordingStatus.classList.add('hidden');
                statusText.textContent = 'Processing...';
                
                updateDebug('Recording stopped');

            } catch (error) {
                console.error('Stop recording error:', error);
                updateDebug(`Stop recording error: ${error.message}`);
            }
        };

        // API Functions
        const startConversation = async () => {
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
                    
                    transcriptionList.innerHTML = '';
                    updateDebug('Conversation started successfully');
                } else {
                    updateDebug('Failed to start conversation');
                    statusText.textContent = 'Failed to start conversation';
                }
            } catch (error) {
                updateDebug(`Error: ${error.message}`);
                statusText.textContent = 'Error starting conversation';
            }
        };

        const uploadAudio = async (audioBlob, segmentNumber) => {
            try {
                const formData = new FormData();
                formData.append('audio', audioBlob, 'recording.webm');
                formData.append('thread_id', currentThreadId);
                formData.append('duration', (Date.now() - recordingStartTime) / 1000);

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
                    if (!isRecording) {
                        statusText.textContent = 'Transcription complete';
                    }
                } else {
                    statusText.textContent = 'Failed to process audio';
                    updateDebug('Failed to process audio');
                }
            } catch (error) {
                updateDebug(`Error uploading audio: ${error.message}`);
                statusText.textContent = 'Error processing audio';
            }
        };

        const addTranscription = (text, timestamp) => {
            const transcriptionDiv = document.createElement('div');
            transcriptionDiv.className = 'bg-white/20 backdrop-blur-sm rounded-lg border border-white/30 p-4 mb-4 transcription-item';
            transcriptionDiv.innerHTML = `
                <div class="text-sm text-white/70 mb-2">${timestamp}</div>
                <div class="text-white">${text}</div>
            `;
            transcriptionList.prepend(transcriptionDiv);
        };

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
        const init = async () => {
            await setupWaveSurfer();
            await loadMicrophones();
            await startConversation();
        };

        init();
    </script>
</body>
</html>