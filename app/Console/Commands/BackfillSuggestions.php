<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\CategoryRecommender;
use Illuminate\Console\Command;

class BackfillSuggestions extends Command
{
    protected $signature = 'smartdocs:backfill-suggestions';
    protected $description = 'Apply rule-based category suggestions to completed documents missing them';

    public function handle(CategoryRecommender $recommender): int
    {
        $n = 0;
        foreach (Document::where('processing_status', 'completed')->whereNull('suggested_category_id')->get() as $doc) {
            if (! $doc->extracted_text) {
                continue;
            }
            $s = $recommender->suggest($doc->extracted_text);
            if ($s) {
                $doc->update(['suggested_category_id' => $s['category_id']]);
                $n++;
            }
        }
        $this->info("backfilled={$n}");

        return self::SUCCESS;
    }
}
