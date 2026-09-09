<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop the existing unique index on `key` before adding composite unique.
            // SQLite auto-indexes UNIQUE constraints with internal names that don't
            // match Laravel's generated index names, so we use raw SQL to handle it.
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'sqlite') {
                // Find and drop any existing index on the key column in SQLite
                $indexes = Schema::getConnection()->select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='settings' AND sql LIKE '%key%'"
                );
                foreach ($indexes as $index) {
                    Schema::getConnection()->statement("DROP INDEX IF EXISTS \"{$index->name}\"");
                }
            } else {
                $table->dropUnique(['key']);
            }
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'key']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            // Restore original unique index on key alone
            $table->unique('key');
        });
    }
};
