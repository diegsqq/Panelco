<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->string('access_level')->default('all_employees')->after('parent_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('access_level');
            $table->index('access_level');
            $table->index('created_by');
        });

        Schema::create('category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('document_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_user');

        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropIndex(['access_level']);
            $table->dropIndex(['created_by']);
            $table->dropColumn(['access_level', 'created_by']);
        });
    }
};
