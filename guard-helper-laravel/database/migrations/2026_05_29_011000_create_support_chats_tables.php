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
        Schema::create('support_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_grant_id')
                ->nullable()
                ->constrained('access_grants')
                ->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('user_email')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->default('open'); // open, closed, resolved
            $table->timestamps();
        });
 
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_thread_id')
                ->constrained('support_threads')
                ->cascadeOnDelete();
            $table->string('sender'); // user, admin
            $table->text('message');
            $table->timestamps();
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_threads');
    }
};
