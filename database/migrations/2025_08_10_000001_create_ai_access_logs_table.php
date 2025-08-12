<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('service',64);
            $table->string('method',128);
            $table->string('tier',40)->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('result_count')->nullable();
            $table->json('params')->nullable();
            $table->string('client_ip',45)->nullable();
            $table->timestamps();
            $table->index(['service','method']);
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_access_logs');
    }
};
