<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('dataset',120)->unique();
            $table->unsignedInteger('current_version')->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_dataset_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('dataset',120);
            $table->unsignedInteger('version');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('source_hash',64)->nullable();
            $table->string('storage_path')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['dataset','version']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ai_dataset_snapshots');
        Schema::dropIfExists('ai_datasets');
    }
};
