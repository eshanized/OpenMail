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
        Schema::create('thread_header_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message_id');
            $table->string('in_reply_to')->nullable();
            $table->text('references')->nullable();
            $table->string('subject')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('folder_path')->nullable();
            $table->unsignedBigInteger('uid')->nullable();
            $table->dateTime('expires_at'); // TTL: 24 hours
            $table->timestamps();

            // O(1) lookup for missing parent Message-IDs
            $table->unique(['user_id', 'message_id']);
            // TTL cleanup index
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_header_cache');
    }
};