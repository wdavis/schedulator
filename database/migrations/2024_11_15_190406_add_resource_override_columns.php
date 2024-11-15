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
        Schema::table('services', function (Blueprint $table) {
            $table->integer('cancellation_window_end')->nullable();
            $table->dropColumn('cancellation_lead');
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->integer('cancellation_window_end_override')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('cancellation_window_end');
            $table->integer('cancellation_lead');
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('booking_window_end_override');
            $table->dropColumn('cancellation_window_end_override');
        });
    }
};
