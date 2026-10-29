<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('pending_email')->nullable()->after('email');
            $table->string('pending_phone', 30)->nullable()->after('phone');
            $table->string('email_change_code', 6)->nullable()->after('pending_email');
            $table->string('phone_change_code', 6)->nullable()->after('pending_phone');
            $table->timestamp('verification_expires_at')->nullable()->after('phone_change_code');
        });

        // Backfill split names from the existing full name.
        DB::table('users')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $parts = preg_split('/\s+/', trim((string) $user->name), 2);
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $parts[0] !== '' ? $parts[0] : null,
                    'last_name' => isset($parts[1]) ? $parts[1] : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name',
                'pending_email', 'pending_phone',
                'email_change_code', 'phone_change_code',
                'verification_expires_at',
            ]);
        });
    }
};
