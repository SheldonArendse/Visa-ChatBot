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

            // Check if relevant entries are found
            if (empty($relevantEntries)) {
                $automatedResponse = "Sorry, I can't assist you with that question. \n\nYour question has been passed on to an agent.";
                Log::info('Bot cannot answer this message: ' . $automatedResponse);

                $conversation[] = ['role' => 'user', 'content' => $userMessage];
                $conversation[] = ['role' => 'assistant', 'content' => $automatedResponse];

                session(['conversation' => $conversation]);

                return redirect()->back()->with('message', $automatedResponse);
            }

            // Calling method to find relevant entries for context
            $context = $this->buildContext($relevantEntries);

            // Get existing conversation from session or instantiate an empty array
            $conversation = session('conversation', []);

            // Create the messages array for API request
            $systemMessage = [
                'role' => 'system',
                'content' => 'You are an assistant specialized in providing information about South African visas. Your responses should be concise and provide all necessary information within 200 tokens or less.'
            ];

            // Include the context from the FAQ document
            $faqContextMessage = [
                'role' => 'system',
                'content' => "Based on the following FAQs:\n\n$context"
            ];

            // Merge the system message, FAQ context, and the conversation history array
            $apiMessages = array_merge([$systemMessage, $faqContextMessage], $conversation);
            $apiMessages[] = ['role' => 'user', 'content' => $userMessage];

            // Post request to OpenAI API
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => $apiMessages,
                    'max_tokens' => 220,
                    'n' => 1,
                    'stop' => null,
                ],
            ]);

            $completion = json_decode($response->getBody()->getContents(), true);
            $message = $completion['choices'][0]['message']['content'];

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


    // Find FAQ query related to the user query
    private function findRelevantEntries($user_query, $training_data)
    {
        $relevant_entries = [];
        // Tokenize user's query into individual words
        $query_tokens = explode(' ', $user_query);
        $idf = $this->calculateIDF($training_data);

        // Loop through each entry in the FAQ document
        foreach ($training_data as $entry) {
            // Tokenize the question in JSON file into individual words
            $entry_tokens = explode(' ', $entry['question']);
            $score = $this->calculateTFIDF($entry_tokens, $query_tokens, $idf);

            // if the TFIDF score is above threshold, add it to relevant_entries array
            if ($score > 0.5) {
                $relevant_entries[] = $entry;
            }
        }

        return $relevant_entries;
    }

    // Calculate IDF for each unique word (token) in the training data
    private function calculateIDF($training_data)
    {
        $idf = [];

        foreach ($training_data as $entry) {
            $entry_tokens = explode(' ', $entry['question']);
            // Create an array for unique tokens
            $unique_tokens = array_unique($entry_tokens);

            // Loop through each unique token in the unique_tokens array
            foreach ($unique_tokens as $token) {
                // if the array is empty set it to 1 else increment by 1
                if (!isset($idf[$token])) {
                    $idf[$token] = 1;
                } else {
                    $idf[$token]++;
                }
            }
        }

        // Total number of entries in the training data
        $num_entries = count($training_data);
        // Loop through each token in the IDF array
        foreach ($idf as $token => $count) {
            // Calculate the IDF value for the token
            $idf[$token] = log($num_entries / $count);
        }

        return $idf;
    }

    // Calculate how relevant a document is to a user's query
    private function calculateTFIDF($entry_tokens, $query_tokens, $idf)
    {
        // Total TFIDF score
        $score = 0;

        // Loop through each token in the query_tokens array (words from user question)
        // Calculate the TF for each token in the loop
        foreach ($query_tokens as $token) {
            $tf = $this->calculateTF($token, $entry_tokens);
            // if token does not exist in the IDF array set the value to 0
            $score += $tf * ($idf[$token] ?? 0);
        }

        return $score;
    }

    // Calculate the term frequency of a token
    private function calculateTF($token, $tokens)
    {
        $count = 0;
        // Loop through each token in the array to measure how frequently a token appears
        // if the tokens match, increment the count by 1
        foreach ($tokens as $t) {
            if ($t === $token) {
                $count++;
            }
        }
        return $count / count($tokens);
    }

    // Constructs a context string from a list of relevant FAQ entries
    private function buildContext($relevant_entries)
    {
        $context = "";
        // loop through each relevant entry in the associative array (Question and Answer)
        foreach ($relevant_entries as $entry) {
            // Concatenate the Q and A to the context string
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
