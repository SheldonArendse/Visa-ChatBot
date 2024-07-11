<!DOCTYPE html>
<html>
<head>
    <title>MonkeyAI</title>
    <link rel="icon" href="{{ asset('images/banana.png') }}" type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="container chat-container">
    <!-- AI logo image -->
    <div class="chat-header">
        <img src="{{ asset('images/monkeyAI-Title.png') }}" alt="AI Logo" id="ai-logo">
    </div>

    <!-- Conversation history and text output -->
    <div class="chat-box">
        @if(session('conversation'))
            @foreach(session('conversation') as $message)
                <div class="chat-message {{ $message['role'] == 'user' ? 'user-message' : 'ai-message-container' }}">
                    @if($message['role'] == 'user')
                        {!! $message['content'] !!}
                    @else
                        <div class="ai-response">
                            <img src="{{ asset('images/banana.png') }}" alt="AI Icon" class="profile-icon">
                            {!! $message['content'] !!}
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

        @if(session('response'))
            <div class="chat-message ai-message-container">
                <div class="ai-response">
                    <img src="{{ asset('images/banana.png') }}" alt="AI Icon" class="profile-icon">
                    {!! session('response') !!}
                </div>
            </div>
        @endif
    </div>

    <!-- User input field and send button -->
    <form method="POST" action="{{ route('openai.request') }}" class="chat-input">
        @csrf
        <input type="text" name="prompt" placeholder="Enter your message">
        <button type="submit"><ion-icon name="paper-plane-outline"></ion-icon></button>
        <a href="{{ route('clear.session') }}" class="btn btn-danger">Clear Chat</a>
    </form>
</div>

<script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
</body>
</html>
