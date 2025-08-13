<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->text('message');
            $table->string('type')->default('chat');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('conversations');
    }
};
