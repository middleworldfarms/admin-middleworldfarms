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
        Schema::table('delivery_completions', function (Blueprint $table) {
            // Add delivery_date field to make completions date-specific
            $table->date('delivery_date')->after('type');
            
            // Drop the old unique constraint
            $table->dropUnique(['external_id', 'type']);
            
            // Add new unique constraint including delivery_date
            $table->unique(['external_id', 'type', 'delivery_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_completions', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique(['external_id', 'type', 'delivery_date']);
            
            // Add back the old unique constraint
            $table->unique(['external_id', 'type']);
            
            // Drop the delivery_date field
            $table->dropColumn('delivery_date');
        });
    }
};
