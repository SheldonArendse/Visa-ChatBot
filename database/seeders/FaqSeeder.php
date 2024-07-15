<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqSeeder extends Seeder
{
    public function run()
    {
        $jsonFile = storage_path('app/training/faqs.json');

        // Log the file path for debugging
        Log::info('JSON file path: ' . $jsonFile);

        if (!file_exists($jsonFile)) {
            Log::error('JSON file not found: ' . $jsonFile);
            return;
        }

        $faqs = json_decode(file_get_contents($jsonFile), true);

        // Log the FAQs being inserted
        Log::info('FAQs to be inserted: ', $faqs);

        foreach ($faqs as $faq) {
            DB::table('faqs')->insert([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
