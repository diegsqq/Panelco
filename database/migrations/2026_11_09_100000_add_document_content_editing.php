<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->longText('content')->nullable();
            $table->text('content_header')->nullable();
            $table->text('content_footer')->nullable();
            $table->timestamp('content_updated_at')->nullable();
            $table->foreignId('content_updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_updated_by');
            $table->dropColumn([
                'content',
                'content_header',
                'content_footer',
                'content_updated_at',
            ]);
        });
    }
};
