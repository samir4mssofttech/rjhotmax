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
        Schema::table('payouts', function (Blueprint $table) {
            // Add proration columns if they don't exist
            if (!Schema::hasColumn('payouts', 'is_prorated')) {
                $table->boolean('is_prorated')->default(false)->after('approved_by');
            }

            if (!Schema::hasColumn('payouts', 'proration_start_date')) {
                $table->date('proration_start_date')->nullable()->after('is_prorated');
            }

            if (!Schema::hasColumn('payouts', 'proration_end_date')) {
                $table->date('proration_end_date')->nullable()->after('proration_start_date');
            }

            if (!Schema::hasColumn('payouts', 'prorated_days')) {
                $table->integer('prorated_days')->nullable()->after('proration_end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['is_prorated', 'proration_start_date', 'proration_end_date', 'prorated_days']);
        });
    }
};
