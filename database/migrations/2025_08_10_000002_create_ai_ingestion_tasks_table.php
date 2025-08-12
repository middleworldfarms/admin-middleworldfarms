<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_ingestion_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 80);
            $table->string('status', 32)->default('pending'); // pending,running,completed,failed
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->json('params')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['type','status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ai_ingestion_tasks');
    }
};
