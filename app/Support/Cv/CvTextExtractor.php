<?php

namespace App\Support\Cv;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class CvTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'pdf' => $this->extractPdf($file->getRealPath()),
            'docx' => $this->extractDocx($file->getRealPath()),
            default => throw new RuntimeException('AI import supports PDF and DOCX files.'),
        };

        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if (mb_strlen($text) < 40) {
            throw new RuntimeException('We could not read enough text from this CV. Try a text-based PDF or DOCX file.');
        }

        return mb_substr($text, 0, 24000);
    }

    private function extractPdf(string $path): string
    {
        return (new Parser())->parseFile($path)->getText();
    }

    private function extractDocx(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('We could not open this DOCX file.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('We could not find readable document text in this DOCX file.');
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tab>/', "\t", $xml) ?? $xml;

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1);
    }
}
