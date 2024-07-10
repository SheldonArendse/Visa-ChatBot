<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class APIController extends Controller {

    public function showForm()
    {
        return view('openai');
    }

    public function makeOpenAIRequest(Request $request)
    {  
        try {
            // Creating Guzzle object for HTTP methods
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Post requests to OpenAI API
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $request->input('prompt'),
                        ],
                    ],
                    'max_tokens' => 50,
                ],
            ]);

            // Decode JSON by converting the JSON string into a PHP array
            $completion = json_decode($response->getBody()->getContents(), true);
            // AI's response is extracted from the array
            $message = $completion['choices'][0]['message']['content'];

            // Get existing conversation from session or instantiate an empty array
            $conversation = session('conversation', []);

            // Append user input and response to the conversation
            $conversation[] = ['role' => 'user', 'content' => $request->input('prompt')];
            $conversation[] = ['role' => 'assistant', 'content' => $message];

            // Store the updated conversation in the session
            session(['conversation' => $conversation]);

            // redirect back to the form page
            return redirect()->back();
            
        } catch (\Exception $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return redirect()->back()->with('response', 'Error: ' . $e->getMessage());
        }
    }
}


