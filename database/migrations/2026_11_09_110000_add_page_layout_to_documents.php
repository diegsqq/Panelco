<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('page_paper', 16)->default('a4');
            $table->string('page_orientation', 16)->default('portrait');
            $table->string('page_margins', 16)->default('normal');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['page_paper', 'page_orientation', 'page_margins']);
        });
    }
};
