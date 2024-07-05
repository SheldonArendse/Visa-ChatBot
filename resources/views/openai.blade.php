<!DOCTYPE html>
<html>
<head>
    <title>OpenAI API Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1 class="mt-5">Test OpenAI API</h1>
    <form method="POST" action="/openai/request">
        @csrf
        <div class="form-group">
            <label for="prompt">Enter your prompt:</label>
            <input type="text" class="form-control" id="prompt" name="prompt" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    @if(session('response'))
        <div class="mt-4">
            <h2>OpenAI Response:</h2>
            <pre>{{ session('response') }}</pre>
        </div>
    @endif
</div>
</body>
</html>
