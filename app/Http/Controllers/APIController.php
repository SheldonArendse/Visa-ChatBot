<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;

class APIController extends Controller
{

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

            // Load the JSON FAQ file
            $faqFile = storage_path('app/training/faqs.json');
            $faqs = json_decode(file_get_contents($faqFile), true);

            // Find relevant entries from FAQs
            $relevantEntries = $this->findRelevantEntries($userMessage, $faqs);
            $context = $this->buildContext($relevantEntries);

            // Get existing conversation from session or instantiate an empty array
            $conversation = session('conversation', []);

            // Create the messages array for API request
            $systemMessage = [
                'role' => 'system',
                'content' => 'You are an assistant speaking with a client specialized in providing information about South African visas. Your responses should:
                    - Be concise and not exceed 200 tokens.
                    - Provide all the necessary information using only 200 tokens or less.'
            ];

            // Merge the system message and the conversation history array
            $apiMessages = array_merge([$systemMessage], $conversation);
            $apiMessages[] = ['role' => 'user', 'content' => $userMessage];

            // Post request to OpenAI API
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Prepare the prompt with context
            $prompt = "You are an expert visa immigration lawyer in South Africa, assisting clients with visa and permit questions. Based on the provided context, please provide a concise and accurate response to the user's query:\n\nContext: $context\n\nUser Query: $userMessage\n\nResponse:";

            $response = $client->post('completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo-instruct',
                    'prompt' => $prompt,
                    'max_tokens' => 150,
                    'n' => 1,
                    'stop' => null,
                ],
            ]);

            $completion = json_decode($response->getBody()->getContents(), true);
            $message = $completion['choices'][0]['text'];

            // Format the response manually
            $formattedMessage = $this->formatResponseManually($message);

            // Append user input and AI response to the conversation
            $conversation[] = ['role' => 'user', 'content' => $userMessage];
            $conversation[] = ['role' => 'assistant', 'content' => $formattedMessage];

            // Use the formatted message as the response
            $response = $formattedMessage;

            // Store the updated conversation in the session
            session(['conversation' => $conversation]);

            // Redirect back to the form page with the AI's response
            return redirect()->back()->with('message', $response);
        } catch (\Exception $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return redirect()->back()->with('response', 'Error: ' . $e->getMessage());
        }
    }

    private function findRelevantEntries($user_query, $training_data)
    {
        $relevant_entries = [];
        $query_tokens = explode(' ', $user_query);
        $idf = $this->calculateIDF($training_data);

        foreach ($training_data as $entry) {
            $entry_tokens = explode(' ', $entry['question']);
            $score = $this->calculateTFIDF($entry_tokens, $query_tokens, $idf);

            if ($score > 0.5) { // Adjust the threshold as needed
                $relevant_entries[] = $entry;
            }
        }

        return $relevant_entries;
    }

    private function calculateIDF($training_data)
    {
        $idf = [];

        foreach ($training_data as $entry) {
            $entry_tokens = explode(' ', $entry['question']);
            $unique_tokens = array_unique($entry_tokens);

            foreach ($unique_tokens as $token) {
                if (!isset($idf[$token])) {
                    $idf[$token] = 1;
                } else {
                    $idf[$token]++;
                }
            }
        }

        $num_entries = count($training_data);
        foreach ($idf as $token => $count) {
            $idf[$token] = log($num_entries / $count);
        }

        return $idf;
    }

    private function calculateTFIDF($entry_tokens, $query_tokens, $idf)
    {
        $score = 0;

        foreach ($query_tokens as $token) {
            $tf = $this->calculateTF($token, $entry_tokens);
            $score += $tf * ($idf[$token] ?? 0);
        }

        return $score;
    }

    private function calculateTF($token, $tokens)
    {
        $count = 0;
        foreach ($tokens as $t) {
            if ($t === $token) {
                $count++;
            }
        }
        return $count / count($tokens);
    }

    private function buildContext($relevant_entries)
    {
        $context = "";
        foreach ($relevant_entries as $entry) {
            $context .= "Question: " . $entry['question'] . "\nAnswer: " . $entry['answer'] . "\n\n";
        }
        return $context;
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
