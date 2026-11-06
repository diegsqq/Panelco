# Document query benchmark and full-text-search decision

## Environment

- Application: SmartDocs (Laravel), local development machine, PHP 8.3
- Benchmark database: temporary SQLite database created by `app/Console/Commands/BenchmarkDocumentQueries.php`
  — never the live `database/database.sqlite`; removed after the run unless `--keep` is passed
- Schema: `documents` table plus `users`, `departments`, `document_categories`, `document_chunks`
- Indexes present: `documents_owner_library_order_index`
  (`uploaded_by, deleted_at, created_at, id`) and
  `documents_processing_freshness_index` (`processing_status, updated_at`) —
  the same indexes added by migration
  `2026_11_06_100000_add_document_query_indexes`
- PHP extensions: sqlite3, zip, sodium

## Volumes used

5,000 synthetic documents spread across 2 users, 1 department, 1 category, and
processing statuses `completed`/`pending`/`failed`/`processing`, plus 15,000
document chunks (3 per document) with realistic title/keyword text so LIKE
searches match small fractions of rows. Titles and text were drawn from an
operational vocabulary (report, budget, safety, inspection, maintenance,
incident, policy, procedure, equipment, training, audit, compliance, etc.).

Rationale: the intended deployment is a departmental OJT prototype; tens of
thousands of documents is a plausible ceiling, so 5,000 documents exercises the
indexed paths with headroom.

## How to re-run

```
php artisan smartdocs:benchmark --documents=5000 --chunks-per-document=3
```

Options: `--documents=N` (default 5,000), `--chunks-per-document=N` (default 3),
`--keep` to retain the temporary database under
`storage/app/private/benchmark/benchmark.sqlite` for inspection.

The command seeds its own temporary database, runs each query 25 times after a
warm-up iteration, and reports min/avg/p95 latency plus whether SQLite used the
new indexes.

## Results (5,000 documents)

| Query | Volume | Min (ms) | Avg (ms) | P95 (ms) | Index used |
|---|---:|---:|---:|---:|:---|
| Library listing (no search) | 5,000 | 0.27 | 0.29 | 0.31 | yes |
| Library listing (search, rare term) | 5,000 | 1.72 | 2.64 | 4.20 | yes |
| Library listing (search, common term) | 5,000 | 0.34 | 0.35 | 0.36 | yes |
| Processing freshness queue | 5,000 | 2.49 | 3.18 | 4.29 | yes |
| Document counts by status | 5,000 | 0.44 | 0.45 | 0.50 | yes |

EXPLAIN QUERY PLAN confirms both new indexes are used:

- Library listing: `SEARCH documents USING INDEX documents_owner_library_order_index (uploaded_by=? AND deleted_at=?)`
- Search over `title`/`storage_path`: same index on the owner/freshness prefix, then a
  scan of the matched rows for the LIKE predicate (substring searches cannot be
  accelerated by ordinary B-tree indexes).
- Processing freshness queue:
  `SEARCH documents USING INDEX documents_processing_freshness_index (processing_status=? AND updated_at<?)`

All measured p95 latencies are well under the ~300 ms threshold at 5,000
documents; even rare-term substring searches complete in single-digit
milliseconds.

## Full-text-search decision

**Not needed now.**

Measured search p95 at 5,000 documents is ~4 ms, orders of magnitude below the
~300 ms threshold, and expected volume for this deployment (tens of thousands of
documents) keeps substring searches on the indexed prefix cheap. Ordinary B-tree
indexes do not accelerate `LIKE '%...%'` over free text, but the current search
scope (titles, keywords, extracted text snippets) plus the owner/freshness index
prefix is adequate at this scale.

### Revisit conditions

Re-assess full-text search (SQLite FTS5 virtual table maintained by triggers,
or a database-appropriate search service if MySQL is adopted in production) when
any of the following is true:

- Document volume approaches or exceeds hundreds of thousands of rows, or
- Measured library/search p95 exceeds ~300 ms against production-like data, or
- Product requires ranked search, phrase search, or search across extracted
  document full text (chunks) rather than title/keyword/metadata fields.

### Tradeoff note

The two new indexes add storage and write overhead on every document insert,
update, and status change. The benchmark only measures read latency; do not add
further indexes without comparing write throughput and query plans against
representative production data.

## Caveats

- Benchmark runs on a local developer machine; production hardware and concurrent
  traffic will change absolute numbers. Re-run after migration to production
  hardware and before declaring production readiness.
- Synthetic data may not reflect real title/keyword distributions or query mixes
  (filters by category, department, status, pagination depth).
- The MySQL production path (per DEPLOYMENT.md) may produce different plans;
  verify indexes under MySQL before production cutover.
