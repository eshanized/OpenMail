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
        Schema::create('pending_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('message_json');           // Full composer data: to, cc, bcc, subject, body, attachments metadata, in_reply_to, references
            $table->text('mime_message')->nullable(); // Pre-built raw MIME string for retry
            $table->timestamp('send_at');           // When to actually send
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->string('sent_folder_uid')->nullable(); // UID from IMAP APPEND to Sent
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            // Composite index for efficient scheduler queries
            $table->index(['user_id', 'status', 'send_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_sends');
    }
};