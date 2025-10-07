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
        Schema::create('crop_rotation_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('previous_crop')->index(); // What was grown before
            $table->string('previous_crop_family')->nullable()->index(); // Crop family
            $table->string('following_crop'); // What to plant next
            $table->string('following_crop_family')->nullable();
            $table->enum('relationship', ['excellent', 'good', 'acceptable', 'poor', 'avoid'])->default('acceptable');
            $table->text('benefits')->nullable(); // Why this rotation works
            $table->text('risks')->nullable(); // Why to avoid (if poor/avoid)
            $table->integer('minimum_gap_months')->nullable(); // Min time between same crop/family
            $table->boolean('breaks_disease_cycle')->default(false);
            $table->boolean('improves_soil_structure')->default(false);
            $table->boolean('fixes_nitrogen')->default(false);
            $table->boolean('depletes_nitrogen')->default(false);
            $table->text('soil_consideration')->nullable(); // e.g., "heavy feeder follows light feeder"
            $table->text('cover_crop_recommendation')->nullable(); // Green manure between crops
            $table->string('source')->nullable();
            $table->integer('confidence_score')->default(5); // 1-10
            $table->timestamps();
            
            // Indexes
            $table->index(['previous_crop', 'relationship'], 'cr_prev_rel_idx');
            $table->index(['previous_crop_family', 'relationship'], 'cr_fam_rel_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_rotation_knowledge');
    }
};
