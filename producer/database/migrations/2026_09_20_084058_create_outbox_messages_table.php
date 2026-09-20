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
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('event_type');
            $table->string('exchange');
            $table->string('routing_key');
        
            $table->json('payload');
            $table->json('headers')->nullable();
        
            $table->timestampTz('occurred_at');
            $table->timestampTz('available_at')->index();
        
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('published_at')->nullable()->index();
        
            $table->timestampsTz();
        
            $table->index(['published_at', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
