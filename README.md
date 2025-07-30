# Darli - Voice Transcription App

Darli is an AI-powered voice transcription assistant built with Laravel and JavaScript. It allows users to record audio and receive real-time transcriptions.

## Features

- Real-time voice recording
- AI-powered transcription
- Live transcription display
- Conversation threading
- Debug panel for troubleshooting

## Technologies Used

- Laravel (PHP framework)
- JavaScript (ES6+)
- TailwindCSS (via CDN)
- Web Audio API
- Fetch API

## Prerequisites

- PHP >= 7.3
- Composer
- Node.js and npm
- Laravel Vite plugin

## Installation

1. Clone the repository:
   git clone https://github.com/your-username/voice-transcription-app.git
   cd voice-transcription-app

2. Install PHP dependencies:
   composer install

3. Copy the `.env.example` file to `.env` and configure your environment variables:
   cp .env.example .env

4. Generate an application key:
   php artisan key:generate

5. Set up your database in the `.env` file and run migrations:
   php artisan migrate

6. Install JavaScript dependencies:
   npm install

7. Compile assets:
   npm run dev

## Usage

1. Start the Laravel development server:
   php artisan serve

2. Open your browser and navigate to `http://localhost:8000`

3. Click "Start Transcription" to begin a new conversation

4. Use the recording button to start and stop voice recording

5. View real-time transcriptions as they appear

## Development

- The main application logic is in `resources/views/transcription/index.blade.php`
- Backend API routes are defined in `routes/api.php`
- The `ConversationController` handles the backend logic