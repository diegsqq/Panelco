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
        'uploaded_by', 'title', 'original_filename', 'storage_path',
        'mime_type', 'file_size', 'document_type', 'department_id',
        'category_id', 'suggested_category_id', 'suggested_department_id',
        'document_date', 'summary', 'keywords', 'extracted_text',
        'processing_status', 'processing_error', 'classification_confidence',
        'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'approved_at' => 'datetime',
            'classification_confidence' => 'decimal:4',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function authorizedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user')->withTimestamps();
    }
}
