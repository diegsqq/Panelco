<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->text('keywords')->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->decimal('department_classification_confidence', 5, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('department_classification_confidence');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
