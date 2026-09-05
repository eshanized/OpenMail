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
        Schema::create('message_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('folder_path');
            $table->unsignedBigInteger('uid');
            $table->string('message_id')->nullable();
            $table->string('subject');
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->string('to_address')->nullable();
            $table->dateTime('date');
            $table->string('snippet')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'folder_path', 'uid']);
            $table->index(['user_id', 'folder_path']);
            $table->index(['user_id', 'message_id']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_metadata');
    }
};