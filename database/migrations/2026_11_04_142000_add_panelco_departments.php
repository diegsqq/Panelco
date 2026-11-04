<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Organization General Management (OGM)',
            'Finance Service Management (FSD)',
            'CSD',
            'ISD',
            'ASD',
            'TSD',
        ] as $name) {
            $exists = DB::table('departments')->where('name', $name)->exists();
            if (! $exists) {
                DB::table('departments')->insert([
                    'name' => $name,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep these departments because administrators may have since assigned users or documents.
    }
};
