<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('payload');
            $table->timestamps();
            $table->index(['type','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
