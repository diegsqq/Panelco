<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkDocumentQueries extends Command
{
    protected $signature = 'smartdocs:benchmark
                            {--documents=5000 : Number of synthetic documents to seed}
                            {--chunks-per-document=3 : Document chunks per document}
                            {--keep : Keep the temporary benchmark database instead of deleting it}';

    protected $description = 'Benchmark document-library and processing queries against a temporary production-like database';

    public function handle(): int
    {
        $documentCount = (int) $this->option('documents');
        $chunksPerDocument = (int) $this->option('chunks-per-document');
        $keep = (bool) $this->option('keep');

        $benchmarkDir = storage_path('app/private/benchmark');
        if (! is_dir($benchmarkDir) && ! mkdir($benchmarkDir, 0700, true) && ! is_dir($benchmarkDir)) {
            $this->error('The benchmark directory could not be created.');

            return self::FAILURE;
        }

        $databasePath = $benchmarkDir.DIRECTORY_SEPARATOR.'benchmark.sqlite';
        if (is_file($databasePath)) {
            unlink($databasePath);
        }
        (new \SQLite3($databasePath))->close();
        if (! file_exists($databasePath)) {
            throw new RuntimeException('The benchmark database file could not be created at '.$databasePath);
        }

        $this->line('Benchmark database: '.$databasePath);
        $this->line('Documents: '.$documentCount.', chunks per document: '.$chunksPerDocument);
        $this->line('');

        config([
            'database.connections.benchmark' => [
                'driver' => 'sqlite',
                'database' => $databasePath,
                'prefix' => '',
            ],
        ]);

        $db = DB::connection('benchmark');

        $this->migrateBenchmarkDatabase($db);
        $this->seedBenchmarkData($db, $documentCount, $chunksPerDocument);

        $results = [];
        $results[] = $this->bench($db, 'Library listing (no search)', fn () => $this->libraryListing($db, null));
        $results[] = $this->bench($db, 'Library listing (search, rare term)', fn () => $this->libraryListing($db, 'qrzplx'));
        $results[] = $this->bench($db, 'Library listing (search, common term)', fn () => $this->libraryListing($db, 'report'));
        $results[] = $this->bench($db, 'Processing freshness queue', fn () => $this->processingFreshness($db));
        $results[] = $this->bench($db, 'Document counts by status', fn () => $this->statusCounts($db));

        $this->table(
            ['Query', 'Volume', 'Min (ms)', 'Avg (ms)', 'P95 (ms)', 'Index used'],
            array_map(fn ($r) => [
                $r['name'], $r['volume'], number_format($r['min'] * 1000, 2),
                number_format($r['avg'] * 1000, 2), number_format($r['p95'] * 1000, 2),
                $r['indexUsed'] ? 'yes' : 'no',
            ], $results)
        );

        $this->line('');
        $this->line('EXPLAIN QUERY PLAN details:');
        $this->line($this->explainLibraryListing($db, null));
        $this->line($this->explainLibraryListing($db, 'report'));
        $this->line($this->explainProcessingFreshness($db));

        if (! $keep && is_file($databasePath)) {
            DB::disconnect('benchmark');
            @unlink($databasePath);
            @unlink($databasePath.'-wal');
            @unlink($databasePath.'-shm');
            $this->line('');
            $this->info('Temporary benchmark database removed.');
        } elseif ($keep) {
            $this->line('');
            $this->info('Temporary benchmark database kept at: '.$databasePath);
        }

        return self::SUCCESS;
    }

    private function migrateBenchmarkDatabase(\Illuminate\Database\Connection $db): void
    {
        $db->statement("PRAGMA journal_mode = WAL");

        $tables = [
            'users' => function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->string('email')->unique();
                $t->string('role')->default('employee'); $t->boolean('is_active')->default(true);
                $t->foreignId('department_id')->nullable()->constrained('departments');
                $t->timestamps();
            },
            'departments' => function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->timestamps();
            },
            'document_categories' => function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->timestamps();
            },
            'documents' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('uploaded_by')->constrained('users');
                $t->foreignId('department_id')->nullable()->constrained('departments');
                $t->foreignId('category_id')->nullable()->constrained('document_categories');
                $t->foreignId('suggested_category_id')->nullable()->constrained('document_categories');
                $t->foreignId('suggested_department_id')->nullable()->constrained('departments');
                $t->string('title'); $t->string('original_filename');
                $t->string('storage_path'); $t->string('mime_type');
                $t->integer('file_size'); $t->date('document_date')->nullable();
                $t->string('processing_status')->default('pending');
                $t->timestamp('deleted_at')->nullable();
                $t->timestamps();
            },
            'document_chunks' => function (Blueprint $t) {
                $t->id();
                $t->foreignId('document_id')->constrained('documents')->onDelete('cascade');
                $t->text('text'); $t->integer('chunk_index');
                $t->timestamps();
            },
        ];

        foreach ($tables as $table => $builder) {
            $schema = $db->getSchemaBuilder();
            if (! $schema->hasTable($table)) {
                $schema->create($table, $builder);
            }
        }

        $schema = $db->getSchemaBuilder();
        if ($schema->hasTable('documents')) {
            $schema->table('documents', function (Blueprint $t) {
                $t->index(['uploaded_by', 'deleted_at', 'created_at', 'id'], 'documents_owner_library_order_index');
                $t->index(['processing_status', 'updated_at'], 'documents_processing_freshness_index');
                $t->index(['processing_status'], 'documents_processing_status_index');
                $t->index(['deleted_at'], 'documents_deleted_at_index');
                $t->index(['uploaded_by'], 'documents_uploaded_by_index');
            });
        }
    }

    private function seedBenchmarkData(\Illuminate\Database\Connection $db, int $documents, int $chunksPerDocument): void
    {
        $words = ['report', 'quarterly', 'budget', 'safety', 'inspection', 'maintenance',
            'incident', 'policy', 'procedure', 'equipment', 'training', 'summary',
            'audit', 'compliance', 'schedule', 'request', 'approval', 'review'];

        $adminId = $db->table('users')->insertGetId([
            'name' => 'Bench Admin', 'email' => 'bench.admin@example.com',
            'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $employeeId = $db->table('users')->insertGetId([
            'name' => 'Bench Employee', 'email' => 'bench.employee@example.com',
            'role' => 'employee', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $deptId = $db->table('departments')->insertGetId([
            'name' => 'Bench Department', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $catId = $db->table('document_categories')->insertGetId([
            'name' => 'Bench Category', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $statuses = ['completed', 'completed', 'completed', 'pending', 'failed', 'processing'];
        $batch = 500;
        $chunkTextPool = [];
        foreach ($words as $w) {
            $chunkTextPool[] = "The {$w} document covers operational details for the bench department.";
        }

        for ($start = 0; $start < $documents; $start += $batch) {
            $end = min($start + $batch, $documents);
            $rows = [];
            $chunkRows = [];
            for ($i = $start; $i < $end; $i++) {
                $now = now()->subDays(random_int(0, 365))->subSeconds(random_int(0, 86400));
                $titleWords = [];
                for ($j = 0; $j < random_int(3, 7); $j++) {
                    $titleWords[] = $words[array_rand($words)];
                }
                $title = implode(' ', $titleWords);
                $documentId = $start + $i + 1;
                $rows[] = [
                    'id' => $documentId,
                    'uploaded_by' => $i % 2 === 0 ? $adminId : $employeeId,
                    'department_id' => $deptId,
                    'category_id' => $catId,
                    'suggested_category_id' => null,
                    'suggested_department_id' => null,
                    'title' => $title,
                    'original_filename' => Str::slug($title).'.txt',
                    'storage_path' => 'documents/'.Str::slug($title).'.txt',
                    'mime_type' => 'text/plain',
                    'file_size' => random_int(1000, 200000),
                    'document_date' => $now->format('Y-m-d'),
                    'processing_status' => $statuses[array_rand($statuses)],
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                for ($c = 0; $c < $chunksPerDocument; $c++) {
                    $chunkRows[] = [
                        'document_id' => $documentId,
                        'text' => $chunkTextPool[array_rand($chunkTextPool)].' chunk '.$c.' of document '.$documentId,
                        'chunk_index' => $c,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            $db->table('documents')->insert($rows);
            $db->table('document_chunks')->insert($chunkRows);
        }
    }

    private function libraryListing(\Illuminate\Database\Connection $db, ?string $search)
    {
        $query = $db->table('documents')
            ->where('uploaded_by', 1)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit(20);

        if ($search !== null) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('storage_path', 'like', $like);
            });
        }

        return $query->get();
    }

    private function processingFreshness(\Illuminate\Database\Connection $db)
    {
        return $db->table('documents')
            ->where('processing_status', 'processing')
            ->where('updated_at', '<=', now()->subMinutes(30))
            ->orderBy('updated_at', 'asc')
            ->get();
    }

    private function statusCounts(\Illuminate\Database\Connection $db)
    {
        return $db->table('documents')
            ->selectRaw("processing_status, count(*) as c")
            ->groupBy('processing_status')
            ->get();
    }

    private function bench(\Illuminate\Database\Connection $db, string $name, \Closure $query, int $iterations = 25): array
    {
        $query(); // warm up
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $query();
            $times[] = microtime(true) - $start;
        }
        sort($times);

        return [
            'name' => $name,
            'volume' => $this->rowCount($db),
            'min' => $times[0],
            'avg' => array_sum($times) / count($times),
            'p95' => $times[(int) ceil(count($times) * 0.95) - 1],
            'indexUsed' => $this->indexUsed($db),
        ];
    }

    private function rowCount(\Illuminate\Database\Connection $db): int
    {
        return (int) $db->table('documents')->count();
    }

    private function indexUsed(\Illuminate\Database\Connection $db): bool
    {
        $plan = $db->select("EXPLAIN QUERY PLAN SELECT * FROM documents WHERE uploaded_by = 1 AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 20");
        foreach ($plan as $row) {
            $text = (string) ($row->detail ?? (string) $row);
            if (str_contains($text, 'documents_owner_library_order_index')
                || str_contains($text, 'documents_processing_freshness_index')
                || str_contains($text, 'documents_processing_status_index')) {
                return true;
            }
        }

        return false;
    }

    private function explainLibraryListing(\Illuminate\Database\Connection $db, ?string $search): string
    {
        $sql = $search === null
            ? "EXPLAIN QUERY PLAN SELECT * FROM documents WHERE uploaded_by = 1 AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 20"
            : "EXPLAIN QUERY PLAN SELECT * FROM documents WHERE uploaded_by = 1 AND deleted_at IS NULL AND (title LIKE '%report%' OR storage_path LIKE '%report%') ORDER BY created_at DESC LIMIT 20";
        $plan = $db->select($sql);

        $lines = ['  library listing'.($search === null ? ' (no search)' : ' (search term)').':'];
        foreach ($plan as $row) {
            $lines[] = '    - '.(string) ($row->detail ?? (string) $row);
        }

        return implode("\n", $lines);
    }

    private function explainProcessingFreshness(\Illuminate\Database\Connection $db): string
    {
        $sql = "EXPLAIN QUERY PLAN SELECT * FROM documents WHERE processing_status = 'processing' AND updated_at <= datetime('now', '-30 minutes') ORDER BY updated_at ASC";
        $plan = $db->select($sql);

        $lines = ['  processing freshness queue:'];
        foreach ($plan as $row) {
            $lines[] = '    - '.(string) ($row->detail ?? (string) $row);
        }

        return implode("\n", $lines);
    }
}
