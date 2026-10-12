<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->text('keywords')->nullable()->after('description');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->text('keywords')->nullable()->after('summary');
            $table->foreignId('suggested_category_id')->nullable()->after('category_id')
                ->constrained('document_categories')->nullOnDelete();
            $table->foreignId('suggested_department_id')->nullable()->after('suggested_category_id')
                ->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_category_id');
            $table->dropConstrainedForeignId('suggested_department_id');
            $table->dropColumn('keywords');
        });
        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
