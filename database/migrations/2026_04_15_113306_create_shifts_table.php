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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();        
            $table->string('name');                    
            $table->enum('type', ['general', 'night', 'rotational']);  // General, Night, Rotational
            $table->time('start_time');
            $table->time('end_time');
            // $table->unsignedTinyInteger('grace_period_minutes')->default(0); // late login grace
            // $table->unsignedSmallInteger('half_day_minutes');  // minutes threshold for half-day
            // $table->boolean('overtime_eligible')->default(false);
            // $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
