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
        Schema::create('user_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('platform');
            $table->boolean('is_working');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('log_id')->nullable();
            $table->timestamps();

            // Setup foreign key index if needed, but since sqlite log might be optional, keep as unsigned integer index.
            $table->foreign('log_id')->references('id')->on('guard_fetch_logs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedbacks');
    }
};
