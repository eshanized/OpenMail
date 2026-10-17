<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_sends', function (Blueprint $table): void {
            $table->boolean('is_scheduled')->default(false)->after('send_at');
            $table->index(
                ['user_id', 'is_scheduled', 'status', 'send_at'],
                'pending_sends_scheduled_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pending_sends', function (Blueprint $table): void {
            $table->dropIndex('pending_sends_scheduled_idx');
            $table->dropColumn('is_scheduled');
        });
    }
};
