<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_metadata', function (Blueprint $table) {
            if (!Schema::hasColumn('message_metadata', 'body_text')) {
                $table->text('body_text')->nullable()->after('snippet');
            }
        });

        // Only add FULLTEXT index for MySQL/MariaDB (Scout database engine)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE message_metadata ADD FULLTEXT INDEX message_metadata_search_idx (subject, from_address, from_name, to_address, snippet, body_text)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE message_metadata DROP INDEX message_metadata_search_idx");
        }
        // Don't drop columns as they may have been added by another migration
    }
};