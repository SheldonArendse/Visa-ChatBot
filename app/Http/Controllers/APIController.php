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
                'content' => 'You are a helpful assistant specialized in answering questions related to South African Visas.'
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

            // Append user input and AI response to the conversation
            $conversation[] = ['role' => 'user', 'content' => $userMessage];
            $conversation[] = ['role' => 'assistant', 'content' => $message];

            // Store the updated conversation in the session
            session(['conversation' => $conversation]);

            // Redirect back to the form page with the AI's response
            return redirect()->back()->with('message', $message);
        
        } catch (\Exception $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return redirect()->back()->with('response', 'Error: ' . $e->getMessage());
        }
    }
}
