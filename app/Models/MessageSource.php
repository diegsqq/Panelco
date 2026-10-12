<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_message_id', 'document_id', 'document_chunk_id',
        'page_number', 'supporting_excerpt', 'relevance_score',
    ];

    protected function casts(): array
    {
        return ['relevance_score' => 'decimal:4'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(DocumentChunk::class, 'document_chunk_id');
    }
}
