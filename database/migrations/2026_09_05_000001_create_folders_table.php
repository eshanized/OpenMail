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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->string('role')->nullable(); // inbox, sent, drafts, trash, spam, archive, null for custom
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('unread_count')->default(0);
            $table->unsignedBigInteger('uidvalidity')->nullable();
            $table->boolean('has_children')->default(false);
            $table->string('parent_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'path']);
            $table->index(['user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
