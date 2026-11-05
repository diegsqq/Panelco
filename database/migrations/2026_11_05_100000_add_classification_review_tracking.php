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
            $table->string('classification_review_status', 32)->default('pending');
            $table->timestamp('classification_reviewed_at')->nullable();
            $table->foreignId('classification_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('classification_review_note')->nullable();
            $table->text('suggested_category_reason')->nullable();
            $table->text('suggested_department_reason')->nullable();
        });

        DB::table('documents')->whereNotNull('approved_at')->update([
            'classification_review_status' => 'approved',
            'classification_reviewed_at' => DB::raw('approved_at'),
            'classification_reviewed_by' => DB::raw('approved_by'),
        ]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('classification_reviewed_by');
            $table->dropColumn([
                'classification_review_status',
                'classification_reviewed_at',
                'classification_review_note',
                'suggested_category_reason',
                'suggested_department_reason',
            ]);
        });
    }
};
