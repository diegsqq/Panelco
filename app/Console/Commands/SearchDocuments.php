<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DocumentRetrievalService;
use Illuminate\Console\Command;

class SearchDocuments extends Command
{
    protected $signature = 'smartdocs:search {query : Question or keywords} {--user=admin@panelco3.local : Login email to search as} {--limit=5 : Max passages}';
    protected $description = 'Search authorized document passages (demo for retrieval)';

    public function handle(DocumentRetrievalService $retrieval): int
    {
        $user = User::where('email', $this->option('user'))->first();
        if (! $user) {
            $this->error('Unknown user.');

            return self::FAILURE;
        }

        $result = $retrieval->retrieve($user, (string) $this->argument('query'), (int) $this->option('limit'));

        if ($result['status'] !== DocumentRetrievalService::STATUS_OK) {
            $this->line(DocumentRetrievalService::statusMessage($result['status']));

            return self::SUCCESS;
        }

        foreach ($result['chunks'] as $i => $hit) {
            /** @var \App\Models\Document $doc */
            $doc = $hit['document'];
            $this->info('#' . ($i + 1) . " [score {$hit['score']}] " . ($doc->title ?? $doc->original_filename));
            $this->line('  Page: ' . ($hit['chunk']->page_number ?? 'n/a') . ' | Doc #' . $doc->id);
            $this->line('  "' . $hit['excerpt'] . '"');
        }

        return self::SUCCESS;
    }
}
