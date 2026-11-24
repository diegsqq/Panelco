<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('upload_batch_id')->nullable()->after('uploaded_by');
            $table->index('upload_batch_id');
        });

        Schema::create('folder_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_files')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_batches');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['upload_batch_id']);
            $table->dropColumn('upload_batch_id');
        });
    }
};
