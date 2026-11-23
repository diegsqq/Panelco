<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->whereNotNull('title')
            ->orderBy('id')
            ->chunkById(200, function ($documents): void {
                foreach ($documents as $document) {
                    $clean = trim((string) $document->title);
                    while (($stripped = trim(preg_replace('/\s*\(\d+\)\s*$/u', '', $clean) ?? $clean)) !== $clean && $stripped !== '') {
                        $clean = $stripped;
                    }

                    if ($clean !== $document->title) {
                        DB::table('documents')
                            ->where('id', $document->id)
                            ->update(['title' => $clean]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Repeat counters cannot be restored.
    }
};
