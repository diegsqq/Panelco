# Product and data policy decisions

## 1. Document expiration

**Decision: retention-review (prompt) only; no automatic archive or delete.**

Current behavior: documents with an `expiration_date` are surfaced to
administrators at Admin → Document expiration review (`/admin/documents/retention-review`),
grouped as "Expired" (past due), "Expires soon" (within 30 days), or current.
Expiration prompts review only — it does not archive or delete documents
automatically, and no document is modified or removed by the scheduler or any
scheduled command.

Rationale:
- Documents may be subject to retention, legal-hold, or audit requirements that
  the application does not yet know about. Automatic deletion risks data loss
  without organizational sign-off.
- The review screen gives administrators a documented opportunity to decide per
  document: extend, archive (download and remove from the library), or leave in
  place.
- Changing to automatic archive/delete requires a signed-off retention/deletion
  policy (see DEPLOYMENT.md §7) and an explicit administrator action, not a
  silent background job.

If the organization later adopts automatic handling, the implementation should:
- add a `retention_action`/`archived_at`/`deleted_at` transition driven by an
  administrator-approved policy, not by the expiration date alone;
- keep audit-log entries for every archived or deleted document;
- preserve the ability to restore within a defined window.

No code change is required for this decision; `app/Http/Controllers/Admin/DocumentRetentionController.php`
already implements review-only behavior.

## 2. Data migration tools

**Decision: none needed. Do not build or adopt data migration tools.**

Assessment:
- This deployment is a fresh SmartDocs installation; there is no legacy document
  repository, no prior schema version in production, and no data to migrate into
  or out of the application.
- Pending schema changes are handled by Laravel migrations
  (`database/migrations`), which are reviewed, applied, and rollback-verified as
  part of deployment (Section 2).
- No concrete migration requirement was identified (no source system, no field
  mappings, no volume estimate, no cutover plan).

If a concrete requirement arises later (for example importing an existing file
share or migrating from SQLite to MySQL), adopt or build migration tooling
against that specific requirement, with rollback and validation steps.
