<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uploaded_by', 'title', 'original_filename', 'storage_path', 'file_hash',
        'version_number', 'current_version_uploaded_by',
        'mime_type', 'file_size', 'document_type', 'department_id',
        'category_id', 'suggested_category_id', 'suggested_department_id',
        'department_classification_confidence',
        'document_date', 'summary', 'keywords', 'extracted_text',
        'expiration_date',
        'processing_status', 'processing_error', 'classification_confidence',
        'approved_at', 'approved_by', 'classification_review_status',
        'classification_reviewed_at', 'classification_reviewed_by', 'classification_review_note',
        'approval_status', 'approval_reviewed_at', 'approval_reviewed_by', 'approval_review_note',
        'suggested_category_reason', 'suggested_department_reason',
        'reference_number', 'sender', 'recipient',
        'content', 'content_header', 'content_footer',
        'content_updated_at', 'content_updated_by',
        'page_paper', 'page_orientation', 'page_margins',
        'workflow_status', 'assigned_to', 'submitted_at', 'reviewed_at',
        'reviewed_by', 'review_decision_note',
        'tags', 'last_opened_at', 'view_count', 'access_level',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expiration_date' => 'date',
            'content_updated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'tags' => 'array',
            'last_opened_at' => 'datetime',
            'approved_at' => 'datetime',
            'classification_reviewed_at' => 'datetime',
            'approval_reviewed_at' => 'datetime',
            'classification_confidence' => 'decimal:4',
            'department_classification_confidence' => 'decimal:4',
        ];
    }

    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'suggested_category_id');
    }

    public function suggestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'suggested_department_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function currentVersionUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_version_uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function contentEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'content_updated_by');
    }

    public function approvalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_reviewed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class)->latest();
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_favorites')->withTimestamps();
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class)->latest();
    }

    public function classificationReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classification_reviewed_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function authorizedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user')->withTimestamps();
    }
}
