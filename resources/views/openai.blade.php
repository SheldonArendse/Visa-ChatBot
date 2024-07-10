<!DOCTYPE html>
<html>
<head>
    <title>OpenAI API Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="container chat-container">
    <!-- AI logo image -->
    <div class="chat-header">
        <img src="{{ asset('images/monkeyAI-Title.png') }}" alt="Visa Logo" id="visa-logo">
    </div>

    <!-- Conversation history and text output -->
    <div class="chat-box">
        @if(session('conversation'))
            @foreach(session('conversation') as $message)
                <div class="chat-message {{ $message['role'] == 'user' ? 'user-message' : 'ai-response' }}">
                    @if($message['role'] == 'user')
                        <strong>{{ ucfirst($message['role']) }}:</strong> {{ $message['content'] }}
                    @else
                        <div class="ai-response">
                            <img src="{{ asset('images/monkey.jpg') }}" alt="AI Icon" class="profile-icon">
                            {{ $message['content'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- User input field and send button -->
    <form method="POST" action="{{ route('openai.request') }}" class="chat-input">
        @csrf
        <input type="text" name="prompt" placeholder="Enter your message" required>
        <button type="submit"><ion-icon name="paper-plane-outline"></ion-icon></button>
    </form>
</div>

<script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
</body>
</html>
