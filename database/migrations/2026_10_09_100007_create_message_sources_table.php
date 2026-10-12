<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_chunk_id')->nullable()->constrained('document_chunks')->nullOnDelete();
            $table->unsignedInteger('page_number')->nullable();
            $table->text('supporting_excerpt')->nullable();
            $table->decimal('relevance_score', 5, 4)->nullable();
            $table->timestamps();
            $table->index(['chat_message_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_sources');
    }
};
