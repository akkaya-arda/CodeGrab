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
        Schema::create('email_platform_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedBigInteger('platform_id');
            
            $table->foreign('platform_id')
                  ->references('id')
                  ->on('platforms')
                  ->onDelete('cascade');

            $table->unique(['email', 'platform_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_platform_assignments');
    }
};
