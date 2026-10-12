<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size');
            $table->string('document_type', 128)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->date('document_date')->nullable();
            $table->text('summary')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->string('processing_status', 32)->default('pending');
            $table->text('processing_error')->nullable();
            $table->decimal('classification_confidence', 5, 4)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('processing_status');
            $table->index('department_id');
            $table->index('category_id');
            $table->index('document_date');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
