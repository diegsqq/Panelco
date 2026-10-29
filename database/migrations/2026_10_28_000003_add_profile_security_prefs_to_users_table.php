<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('job_title', 100)->nullable()->after('avatar_path');
            $table->string('organization', 150)->nullable()->after('job_title');
            // Preferences
            $table->string('theme', 10)->default('light')->after('locale');
            $table->string('locale', 5)->default('en')->after('organization');
            $table->boolean('notify_doc_done')->default(true)->after('theme');
            $table->boolean('notify_doc_failed')->default(true)->after('notify_doc_done');
            $table->boolean('notify_login_alert')->default(false)->after('notify_doc_failed');
            // Two-factor authentication (TOTP)
            $table->boolean('two_factor_enabled')->default(false)->after('notify_login_alert');
            $table->string('two_factor_secret', 64)->nullable()->after('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path', 'job_title', 'organization',
                'theme', 'locale',
                'notify_doc_done', 'notify_doc_failed', 'notify_login_alert',
                'two_factor_enabled', 'two_factor_secret',
            ]);
        });
    }
};
