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
        Schema::create('variety_audit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained('plant_varieties')->onDelete('cascade');
            $table->string('audit_run_id')->nullable()->index(); // Group results from same audit run
            
            // Issue details
            $table->text('issue_description');
            $table->enum('severity', ['critical', 'warning', 'info'])->default('info');
            $table->enum('confidence', ['high', 'medium', 'low'])->default('medium');
            
            // Suggestion details
            $table->string('suggested_field')->nullable(); // e.g., 'maturity_days', 'spacing'
            $table->text('current_value')->nullable();
            $table->text('suggested_value')->nullable();
            
            // Review workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for filtering
            $table->index(['status', 'severity']);
            $table->index(['variety_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variety_audit_results');
    }
};
