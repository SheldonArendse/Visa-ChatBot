<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class APIController extends Controller {

    public function showForm()
    {
        return view('openai');
    }

    public function makeOpenAIRequest(Request $request)
    {
        try {
            // Get the user input from the request or fetch the latest message from the database
            $userMessage = $request->input('prompt');

            if (!$userMessage) {
                // Fetch latest message from the database if no input provided
                $latestMessage = DB::table('fb_api_msg')->orderBy('MsgID', 'desc')->first();

                // Throw error message if there's no messages in the database
                if (!$latestMessage) {
                    throw new \Exception('No messages found in the database.');
                }

                $userMessage = $latestMessage->MsgBody;
                Log::info('Fetched message from database: ' . $userMessage);
            } else {
                Log::info('User provided message: ' . $userMessage);
            }

            // Creating Guzzle object for HTTP methods
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Get existing conversation from session or instantiate an empty array
            $conversation = session('conversation', []);

            // Add system message to provide context for the AI
            $systemMessage = [
                'role' => 'system',
                'content' => 'You are a helpful assistant specialized in answering questions related to South African Visas. Provide brief answers only'
            ];

            // Create the messages array for API request
            $apiMessages = array_merge([$systemMessage], $conversation);
            $apiMessages[] = ['role' => 'user', 'content' => $userMessage];

            // Post request to OpenAI API
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => $apiMessages,
                    'max_tokens' => 150,
                ],
            ]);

            // Decode JSON by converting the JSON string into a PHP array
            $completion = json_decode($response->getBody()->getContents(), true);
            // AI's response is extracted from the array
            $message = $completion['choices'][0]['message']['content'];

            // Format the response manually
            $formattedMessage = $this->formatResponseManually($message);

            // Append user input and AI response to the conversation
            $conversation[] = ['role' => 'user', 'content' => $userMessage];
            $conversation[] = ['role' => 'assistant', 'content' => $formattedMessage];

            // Store the updated conversation in the session
            session(['conversation' => $conversation]);

            // Redirect back to the form page with the AI's response
            return redirect()->back()->with('message', $formattedMessage);
        
        } catch (\Exception $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return redirect()->back()->with('response', 'Error: ' . $e->getMessage());
        }
    }

    private function formatResponseManually($response)
    {
        // Convert new lines to <br>
        $response = nl2br($response);

        // Handle bold text
        $response = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $response);

        // Handle italic text
        $response = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $response);

        // Handle unordered lists
        $response = preg_replace('/\n- (.*?)(?=\n|$)/', '<ul><li>$1</li></ul>', $response);

        // Handle ordered lists
        $response = preg_replace('/\n\d+\. (.*?)(?=\n|$)/', '<ol><li>$1</li></ol>', $response);

        // Handle nested lists
        $response = preg_replace('/<\/ul>\s*<ul>/', '', $response);
        $response = preg_replace('/<\/ol>\s*<ol>/', '', $response);

        return $response;
    }
}

