<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
            $table->integer('fetch_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table) {
            //
        });
    }
};
