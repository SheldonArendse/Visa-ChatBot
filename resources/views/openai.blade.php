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
        @if(session('response'))
            <div class="chat-message ai-response">
                {{ session('response') }}
            </div>
        @endif
    </div>

    <form method="POST" action="/openai/request" class="chat-input">
        @csrf
        <input type="text" name="prompt" placeholder="Enter your message" required>
        <button type="submit"><ion-icon name="paper-plane-outline"></ion-icon></button>
    </form>

</div>
</body>

<script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
</html>
