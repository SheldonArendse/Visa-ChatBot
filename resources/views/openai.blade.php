<!DOCTYPE html>
<html>

<head>
    <title>Banana.ai</title>
    <link rel="icon" href="{{ asset('images/banana.png') }}" type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="container chat-container">
        <!-- AI logo image -->
        <div class="chat-header">
            <img src="{{ asset('images/monkeyAI-Title.png') }}" alt="AI Logo" id="ai-logo" draggable="false">
        </div>

        <!-- Conversation history and text output -->
        <div class="chat-box" id="chat-box">
            @if(session('conversation'))
            @foreach(session('conversation') as $message)
            <div class="chat-message {{ $message['role'] == 'user' ? 'user-message' : 'ai-message-container' }}">
                @if($message['role'] == 'user')
                {!! $message['content'] !!}
                @else
                <div class="ai-response">
                    <img src="{{ asset('images/banana.png') }}" alt="AI Icon" class="profile-icon" draggable="false">
                    {!! $message['content'] !!}
                </div>
                @endif
            </div>
            @endforeach
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

    <script>
        // Scroll to the bottom of the chat box
        function scrollToBottom() {
            var chatBox = document.getElementById('chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Call scrollToBottom when the page loads or updates
        window.onload = scrollToBottom;
    </script>

</body>

</html>