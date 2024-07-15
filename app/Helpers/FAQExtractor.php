<?php

namespace App\Helpers;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use Illuminate\Support\Facades\Log;

class FAQExtractor
{
    public static function extractFaqsFromDocx($filePath)
    {
        Log::info('Loading file: ' . $filePath);

        // Load the Word document
        $phpWord = IOFactory::load($filePath, 'Word2007');
        $faqs = [];

        Log::info('File loaded successfully.');

        // Iterate through sections and elements to extract text
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table) {
                    foreach ($element->getRows() as $row) {
                        $cells = $row->getCells();
                        if (count($cells) >= 2) {
                            $question = self::extractTextFromCell($cells[0]);
                            $answer = self::extractTextFromCell($cells[1]);
                            $faqs[] = [
                                'question' => $question,
                                'answer' => $answer,
                            ];
                        }
                    }
                }
            }
        }

        // Log the extracted FAQs to verify
        Log::info('Extracted FAQs: ' . json_encode($faqs));

        return $faqs;
    }

    private static function extractTextFromCell($cell)
    {
        $text = '';
        foreach ($cell->getElements() as $element) {
            if ($element instanceof TextRun) {
                foreach ($element->getElements() as $childElement) {
                    if ($childElement instanceof Text) {
                        $text .= $childElement->getText() . " ";
                    }
                }
            } elseif ($element instanceof Text) {
                $text .= $element->getText() . " ";
            }
        }
        return trim($text);
    }

    public static function saveFaqsAsJson($faqs, $filePath)
    {
        $json = json_encode($faqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $json);

        // Log the saved JSON file path
        Log::info('JSON file saved at: ' . $filePath);
    }
}
