<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop the existing unique index on `key` before adding composite unique
            $table->dropIndex(['key']);
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
