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
            $table->string('approval_status', 32)->default('pending');
            $table->timestamp('approval_reviewed_at')->nullable();
            $table->foreignId('approval_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_review_note')->nullable();
        });

        DB::table('documents')->where('classification_review_status', 'approved')->update([
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_reviewed_by');
            $table->dropColumn([
                'approval_status',
                'approval_reviewed_at',
                'approval_review_note',
            ]);
        });
    }
};
