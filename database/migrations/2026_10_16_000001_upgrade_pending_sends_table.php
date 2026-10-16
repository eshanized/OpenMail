<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pending_sends', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
            $table->string('idempotency_key', 64)->nullable()->unique()->after('user_id');
            $table->string('message_id', 255)->nullable()->index()->after('idempotency_key');
            $table->string('claim_token', 64)->nullable()->index()->after('status');
            $table->timestamp('claimed_at')->nullable()->after('claim_token');
            $table->boolean('sent_folder_synced')->default(false)->after('sent_folder_uid');
            $table->boolean('smtp_accepted')->default(false)->after('sent_folder_synced');
            $table->timestamp('delivered_at')->nullable()->after('smtp_accepted');
            $table->unsignedSmallInteger('max_retries')->default(3)->after('retry_count');
            $table->string('last_error_category', 64)->nullable()->after('max_retries');
            $table->text('last_error')->nullable()->after('last_error_category');
            $table->longText('mime_message')->nullable()->change();

            $table->index(['status', 'send_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_sends', function (Blueprint $table) {
            $table->dropIndex(['status', 'send_at']);
            $table->dropColumn([
                'idempotency_key',
                'message_id',
                'claim_token',
                'claimed_at',
                'sent_folder_synced',
                'smtp_accepted',
                'delivered_at',
                'max_retries',
                'last_error_category',
                'last_error',
            ]);
            $table->string('status', 20)->default('pending')->change();
            $table->text('mime_message')->nullable()->change();
        });
    }
};
