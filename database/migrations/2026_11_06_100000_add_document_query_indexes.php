<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->index(
                ['uploaded_by', 'deleted_at', 'created_at', 'id'],
                'documents_owner_library_order_index'
            );
            $table->index(
                ['processing_status', 'updated_at'],
                'documents_processing_freshness_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_owner_library_order_index');
            $table->dropIndex('documents_processing_freshness_index');
        });
    }
};
