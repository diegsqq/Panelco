<?php

namespace App\Services;

use App\Models\Document;

class DocumentChunker
{
    /**
     * Rebuild chunks for a document. Deletes existing chunks first so
     * retries never create duplicate index entries.
     *
     * @param  array<int, string>  $pages  page number => text
     */
    public function chunk(Document $document, string $fullText, array $pages = []): void
    {
        $document->chunks()->delete();

        $perChunk = max(50, (int) config('smartdocs.chunk.words', 200));
        $index = 0;

        if (! empty($pages)) {
            foreach ($pages as $pageNo => $pageText) {
                foreach ($this->splitWords($pageText, $perChunk) as $piece) {
                    $document->chunks()->create([
                        'chunk_index' => $index++,
                        'content' => $piece,
                        'page_number' => $pageNo,
                    ]);
                }
            }

            return;
        }

        foreach ($this->splitWords($fullText, $perChunk) as $piece) {
            $document->chunks()->create([
                'chunk_index' => $index++,
                'content' => $piece,
                'page_number' => null,
            ]);
        }
    }

    /** @return list<string> */
    private function splitWords(string $text, int $perChunk): array
    {
        $words = preg_split('/\s+/', trim($text));
        $words = array_values(array_filter($words));
        if (empty($words)) {
            return [];
        }

        $out = [];
        foreach (array_chunk($words, $perChunk) as $slice) {
            $out[] = implode(' ', $slice);
        }

        return $out;
    }
}
