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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('environment_id');
            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
            $table->integer('duration')->default(15);
            $table->integer('buffer_before')->default(0);
            $table->integer('buffer_after')->default(0);
            $table->integer('booking_window_lead')->default(0); // how early they can schedule - 60 days, or 60 * 24 * 60 minutes
            $table->integer('booking_window_end')->default(0); // how late they can schedule - 120 minutes before
            $table->integer('cancellation_lead')->default(1440); // how early they can cancel - 24 hours

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
