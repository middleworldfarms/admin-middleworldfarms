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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false)->after('status');
            $table->timestamp('completed_at')->nullable()->after('is_completed');
            $table->string('completed_by')->nullable()->after('completed_at'); // Staff member who marked it complete
            $table->text('completion_notes')->nullable()->after('completed_by'); // Optional notes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['is_completed', 'completed_at', 'completed_by', 'completion_notes']);
        });
    }
};
