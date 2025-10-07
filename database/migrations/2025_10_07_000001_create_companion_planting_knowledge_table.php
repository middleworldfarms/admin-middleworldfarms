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
        Schema::create('companion_planting_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('primary_crop')->index(); // e.g., 'Brussels Sprouts', 'Cauliflower'
            $table->string('primary_crop_family')->nullable()->index(); // e.g., 'Brassica', 'Allium'
            $table->string('companion_plant'); // e.g., 'Garlic', 'Nasturtium'
            $table->string('companion_family')->nullable(); // e.g., 'Allium', 'Tropaeolaceae'
            $table->enum('relationship_type', ['beneficial', 'neutral', 'avoid'])->default('beneficial');
            $table->text('benefits')->nullable(); // Why this works
            $table->text('planting_notes')->nullable(); // How and when to plant together
            $table->string('planting_timing')->nullable(); // e.g., 'Plant companion at transplant time', 'Underplant in autumn'
            $table->string('spacing_notes')->nullable(); // e.g., 'Between rows', 'Bed edges', 'Underplanted'
            $table->enum('intercrop_type', ['simultaneous', 'sequential', 'relay', 'underplant'])->nullable();
            $table->integer('days_to_harvest_companion')->nullable(); // How long before companion is harvested
            $table->boolean('quick_crop')->default(false); // Is this a fast intercrop?
            $table->text('seasonal_considerations')->nullable(); // Timing/season specific notes
            $table->string('source')->nullable(); // Reference source for this knowledge
            $table->integer('confidence_score')->default(5); // 1-10, how confident are we in this data
            $table->timestamps();
            
            // Indexes for fast querying with shorter names
            $table->index(['primary_crop', 'relationship_type'], 'cp_knowledge_crop_rel_idx');
            $table->index(['primary_crop_family', 'relationship_type'], 'cp_knowledge_family_rel_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companion_planting_knowledge');
    }
};
