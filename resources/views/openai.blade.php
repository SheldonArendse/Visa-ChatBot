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
        <img src="{{ asset('images/visa-blue.jpg') }}" alt="Visa Logo">
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
        <button type="submit"><img src="https://cdn-icons-png.flaticon.com/512/130/130925.png" alt="Send"></button>
    </form>

</div>
</body>
</html>
