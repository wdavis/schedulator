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
        Schema::create('schedule_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('resource_id');
            $table->uuid('location_id');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('type');
            $table->timestamps();

            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_overrides');
    }
};
