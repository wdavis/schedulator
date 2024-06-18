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
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('active')
                ->default(false);
            $table->uuid('environment_id');
            $table->integer('buffer_before_override')->nullable();
            $table->integer('buffer_after_override')->nullable();
            $table->integer('booking_window_lead_override')->nullable();
            $table->integer('booking_window_end_override')->nullable();
            $table->integer('cancellation_lead_override')->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
