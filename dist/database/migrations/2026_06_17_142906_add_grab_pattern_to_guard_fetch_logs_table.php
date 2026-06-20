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
        Schema::table('guard_fetch_logs', function (Blueprint $table) {
            $table->string('grab_pattern')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guard_fetch_logs', function (Blueprint $table) {
            $table->dropColumn('grab_pattern');
        });
    }
};
