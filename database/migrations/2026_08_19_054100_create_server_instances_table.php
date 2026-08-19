<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the server_instances table.
     * 
     * This table stores information about server instances
     * used in the load balancing system.
     */
    public function up(): void
    {
        Schema::create('server_instances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port');
            $table->string('hostname');
            $table->boolean('is_online')->default(true);
            $table->integer('request_count')->default(0);
            $table->integer('active_connections')->default(0);
            $table->integer('weight')->default(1);
            $table->timestamp('last_heartbeat')->nullable();
            $table->string('algorithm')->default('round_robin');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drop the server_instances table.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_instances');
    }
};
