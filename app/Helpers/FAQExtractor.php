<?php

namespace App\Helpers;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextBreak;
use Illuminate\Support\Facades\Log;

class FAQExtractor
{
    public static function extractFaqsFromDocx($filePath)
    {
        Log::info('Loading file: ' . $filePath);

        // Load the Word document
        $phpWord = IOFactory::load($filePath, 'Word2007');
        $text = '';

        Log::info('File loaded successfully.');

        // Iterate through sections and elements to extract text
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    Log::info('Found TextRun element.');
                    foreach ($element->getElements() as $childElement) {
                        if ($childElement instanceof Text) {
                            $text .= $childElement->getText() . "\n";
                            Log::info('TextRun child text: ' . $childElement->getText());
                        }
                    }
                } elseif ($element instanceof Text) {
                    $text .= $element->getText() . "\n";
                    Log::info('Text element: ' . $element->getText());
                } elseif ($element instanceof Table) {
                    Log::info('Found Table element.');
                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            foreach ($cell->getElements() as $cellElement) {
                                if ($cellElement instanceof TextRun) {
                                    foreach ($cellElement->getElements() as $childElement) {
                                        if ($childElement instanceof Text) {
                                            $text .= $childElement->getText() . "\n";
                                            Log::info('Table cell text: ' . $childElement->getText());
                                        }
                                    }
                                } elseif ($cellElement instanceof Text) {
                                    $text .= $cellElement->getText() . "\n";
                                    Log::info('Table cell text: ' . $cellElement->getText());
                                }
                            }
                        }
                    }
                } elseif ($element instanceof TextBreak) {
                    $text .= "\n";
                    Log::info('TextBreak element.');
                } else {
                    Log::info('Other element: ' . get_class($element));
                }
            }
        }

        // Log the extracted text to verify
        Log::info('Extracted Text: ' . $text);

        // Split text by double newline to separate questions and answers
        $faqs = explode("\n\n", $text);

        // Log the split FAQ parts
        Log::info('Split FAQs: ' . json_encode($faqs));

        $formattedFaqs = [];
        foreach ($faqs as $faq) {
            $parts = explode("\n", $faq);
            if (count($parts) >= 2) {
                $question = trim($parts[0]);
                $answer = trim(implode(" ", array_slice($parts, 1)));
                $formattedFaqs[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
        }

        // Log the formatted FAQs
        Log::info('Formatted FAQs: ' . json_encode($formattedFaqs));

        return $formattedFaqs;
    }

    public static function saveFaqsAsJson($faqs, $filePath)
    {
        $json = json_encode($faqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $json);

        // Log the saved JSON file path
        Log::info('JSON file saved at: ' . $filePath);
    }
}
