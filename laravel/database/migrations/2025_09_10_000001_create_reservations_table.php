<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number')->index();
            $table->dateTime('departure_time')->index();
            $table->enum('status',['PENDING','CONFIRMED','CANCELLED','CHECKED_IN'])->default('PENDING')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reservations'); }
};
