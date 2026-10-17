<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snoozed_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('folder_path', 255);
            $table->unsignedBigInteger('uid');
            $table->string('message_id', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('sender', 320)->nullable();
            $table->timestamp('wake_at');
            $table->string('status', 24)->default('snoozed');
            $table->timestamps();

            $table->unique(['user_id', 'folder_path', 'uid'], 'snoozed_messages_location_unique');
            $table->index(['user_id', 'status', 'wake_at'], 'snoozed_messages_wakeup_idx');
            $table->index(['user_id', 'folder_path', 'status'], 'snoozed_messages_folder_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snoozed_messages');
    }
};
