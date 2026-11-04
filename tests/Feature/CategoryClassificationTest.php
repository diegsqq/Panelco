<?php

namespace Tests\Feature;

use App\Services\CategoryRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_matching_uses_complete_words_and_phrases(): void
    {
        $this->seed();
        $recommender = app(CategoryRecommender::class);

        $unrelatedWords = $recommender->suggest('The premaintenance process reviewed memory allocation and memorabilia.');
        $this->assertSame(
            'Other Documents',
            \App\Models\DocumentCategory::findOrFail($unrelatedWords['category_id'])->name
        );

        $outageReport = $recommender->suggest("OUTAGE\nREPORT: The feeder was restored after the interruption.");
        $this->assertSame(
            'Network Incident Reports',
            \App\Models\DocumentCategory::findOrFail($outageReport['category_id'])->name
        );
    }

    public function test_technical_terms_are_classified_without_financial_keyword_boosts(): void
    {
        $this->seed();

        $result = app(CategoryRecommender::class)->suggest(
            'Load-flow analysis measured voltage regulation for the distribution system.'
        );

        $this->assertSame(
            'Technical Reports',
            \App\Models\DocumentCategory::findOrFail($result['category_id'])->name
        );
    }
}
