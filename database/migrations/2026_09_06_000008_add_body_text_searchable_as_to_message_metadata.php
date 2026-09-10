<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_metadata', function (Blueprint $table) {
            if (! Schema::hasColumn('message_metadata', 'body_text')) {
                $table->text('body_text')->nullable()->after('snippet');
            }
            if (! Schema::hasColumn('message_metadata', 'searchable_as')) {
                $table->string('searchable_as')->nullable()->after('body_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_metadata', function (Blueprint $table) {
            $table->dropColumn(['body_text', 'searchable_as']);
        });
    }
};
