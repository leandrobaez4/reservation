<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('passengers', function (Blueprint $table) {
            if (Schema::hasColumn('passengers', 'reservation_id')) {
                $table->dropForeign(['reservation_id']);
                $table->dropColumn('reservation_id');
            }
        });
    }

    public function down(): void {
        Schema::table('passengers', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
