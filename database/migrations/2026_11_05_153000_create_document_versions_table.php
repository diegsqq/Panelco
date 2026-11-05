<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('version_number')->default(1);
            $table->foreignId('current_version_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('documents')->update([
            'current_version_uploaded_by' => DB::raw('uploaded_by'),
        ]);

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('file_hash')->nullable();
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_version_uploaded_by');
            $table->dropColumn('version_number');
        });
    }
};
