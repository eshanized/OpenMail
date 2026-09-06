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
        Schema::table('message_metadata', function (Blueprint $table) {
            $table->text('body_text')->nullable()->after('snippet');
            $table->string('searchable_as')->nullable()->after('body_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_metadata', function (Blueprint $table) {
            $table->dropColumn(['body_text', 'searchable_as']);
        });
    }
};
