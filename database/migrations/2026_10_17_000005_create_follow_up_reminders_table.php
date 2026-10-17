<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('folder_path', 255);
            $table->unsignedBigInteger('uid');
            $table->string('message_id', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('sender', 320)->nullable();
            $table->timestamp('remind_at');
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'folder_path', 'uid'], 'follow_up_reminders_location_unique');
            $table->index(['user_id', 'status', 'remind_at'], 'follow_up_reminders_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_reminders');
    }
};
