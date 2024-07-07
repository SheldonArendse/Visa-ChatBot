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
            $response = $client->post('completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'prompt' => $request->input('prompt'),
                    'max_tokens' => 50,
                ],
            ]);

            // Decode JSON by converting the JSON string into a PHP variable
            $completion = json_decode($response->getBody()->getContents(), true);

            return response()->json($completion);
        } catch (\Exception $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return response()->json(['ERROR' => 'Request failed', 'Message' => $e->getMessage()], 500);
            }
    }
}


