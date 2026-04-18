<?php

use App\Enums\Attendance;
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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade'); // ADD THIS
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->unsignedSmallInteger('worked_minutes')->nullable();    // computed
            $table->unsignedSmallInteger('overtime_minutes')->default(0); // computed
            $table->boolean('is_late')->default(false);
            $table->string('status')->default(Attendance::PRESENT);
            $table->string('remarks')->nullable();
            $table->foreignId('entered_by')->constrained('users')->onDelete('cascade'); // HR user
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
