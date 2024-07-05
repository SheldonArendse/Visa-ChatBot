<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class APIController extends Controller 
{
    public function makeOpenAIRequest()
    {
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
                'model' => 'text-davinci-003',
                'prompt' => 'Once upon a time',
                'max_tokens' => 50,
            ],
        ]);

        // Decode JSON by converting the JSON string into a PHP variable
        $completion = json_decode($response->getBody()->getContents(), true);

        return response()->json($completion);
    }
}