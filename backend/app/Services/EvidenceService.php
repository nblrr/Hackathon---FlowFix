<?php

namespace App\Services;

use App\Models\Document;

class EvidenceService
{
    /** Resolve only literal quotes; a model-provided page number is never trusted. */
    public function page(?Document $document, ?string $quote): ?int
    {
        if (!$document || !$quote || !in_array($document->extraction_status,['ready','needs_review'])) return null;
        $quote=$this->normalize($quote);
        if (mb_strlen($quote)<3) return null;
        foreach ($document->pages??[] as $page) {
            if (str_contains($this->normalize($page['text']),$quote)) return $page['page'];
        }
        return null;
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/u',' ',trim($text));
    }
}
