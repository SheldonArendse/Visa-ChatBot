<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\FAQExtractor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ConvertWordToJson extends Command
{
    protected $signature = 'convert:word-to-json {filePath}';
    protected $description = 'Convert Word document to JSON';

    public function handle()
    {
        $filePath = storage_path('app/' . $this->argument('filePath'));

        $faqs = FAQExtractor::extractFaqsFromDocx($filePath);

        Log::info('FAQs to be saved as JSON: ', $faqs);

        // Save JSON in the storage/app/training directory
        $jsonFilePath = 'training/faqs.json';
        Storage::put($jsonFilePath, json_encode($faqs, JSON_PRETTY_PRINT));

        $this->info('JSON file created at ' . storage_path('app/' . $jsonFilePath));
    }
}
