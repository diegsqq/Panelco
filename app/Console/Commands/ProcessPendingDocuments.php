<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Console\Command;

class ProcessPendingDocuments extends Command
{
    protected $signature = 'smartdocs:process {--limit=20 : Max pending documents to process}';
    protected $description = 'Process pending documents synchronously (no queue worker needed)';

    public function handle(): int
    {
        $docs = Document::whereIn('processing_status', ['pending', 'processing'])
            ->orderBy('id')->limit((int) $this->option('limit'))->get();

        if ($docs->isEmpty()) {
            $this->info('Nothing pending.');

            return self::SUCCESS;
        }

        foreach ($docs as $doc) {
            app()->call([new ProcessDocument($doc->id), 'handle']);
            $doc->refresh();
            $this->line("#{$doc->id} {$doc->original_filename} → {$doc->processing_status}");
        }

        return self::SUCCESS;
    }
}
