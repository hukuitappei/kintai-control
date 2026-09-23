<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('breaks', function (Blueprint $table) {
            $table->id();

            // 外部キー: attendance_records(id)、ON DELETE CASCADE（docs/table-design.md 3章）。
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();

            // docs/table-design.md 3章を見て埋める（attendance_recordsと同じ形）。
            $table->time('break_in');
            $table->time('break_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breaks');
    }
};
