<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the request_logs table.
     * 
     * This table logs all requests handled by server instances
     * for analytics and monitoring purposes.
     */
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_instance_id')->constrained()->onDelete('cascade');
            $table->string('method');
            $table->string('path');
            $table->string('client_ip');
            $table->integer('response_time')->nullable();
            $table->integer('status_code');
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drop the request_logs table.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
