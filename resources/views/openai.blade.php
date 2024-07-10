<!DOCTYPE html>
<html>
<head>
    <title>OpenAI API Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="container chat-container">
    <div class="chat-header">
        <img src="{{ asset('images/visa-no-bg.png') }}" alt="Visa Logo" id="visa-logo">
    </div>

    <div class="chat-box">
        @if(session('conversation'))
            @foreach(session('conversation') as $message)
                <div class="chat-message {{ $message['role'] == 'user' ? 'user-message' : 'ai-response' }}">
                    <strong>{{ ucfirst($message['role']) }}:</strong> {{ $message['content'] }}
                </div>
            @endforeach
        @endif
    </div>

    <form method="POST" action="{{ route('openai.request') }}" class="chat-input">
        @csrf
        <input type="text" name="prompt" placeholder="Enter your message" required>
        <button type="submit"><ion-icon name="paper-plane-outline"></ion-icon></button>
    </form>
</div>

<script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
</body>
</html>
