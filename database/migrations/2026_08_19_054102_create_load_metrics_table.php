<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the load_metrics table.
     * 
     * This table stores performance metrics for server instances
     * and load balancing algorithms.
     */
    public function up(): void
    {
        Schema::create('load_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_instance_id')->constrained()->onDelete('cascade');
            $table->string('algorithm');
            $table->integer('total_requests')->default(0);
            $table->decimal('avg_response_time', 10, 2)->default(0);
            $table->integer('peak_load')->default(0);
            $table->timestamp('peak_load_time')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drop the load_metrics table.
     */
    public function down(): void
    {
        Schema::dropIfExists('load_metrics');
    }
};
