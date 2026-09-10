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
        Schema::create('message_labels', function (Blueprint $table) {
            $table->foreignId('message_metadata_id')->constrained('message_metadata')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['message_metadata_id', 'label_id']);

            // Per Pitfall 5: index for label filter performance
            $table->index(['user_id', 'label_id', 'message_metadata_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_labels');
    }
};
