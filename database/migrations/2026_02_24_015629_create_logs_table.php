<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('id_num', 50);
            $table->date('date_today');
            $table->timestamp('time_in')->nullable();
            $table->timestamp('time_out')->nullable();
            $table->timestamp('break1_out')->nullable(); // morning break start (15 mins)
            $table->timestamp('break1_in')->nullable();  // morning break end
            $table->timestamp('break2_out')->nullable(); // lunch break start (1 hr)
            $table->timestamp('break2_in')->nullable();  // lunch break end
            $table->timestamp('break3_out')->nullable(); // afternoon break start (15 mins)
            $table->timestamp('break3_in')->nullable();  // afternoon break end
            $table->enum('status', ['present', 'late', 'absent', 'half-day'])->default('present');
            $table->integer('overtime_minutes')->default(0);
            $table->timestamps();

            $table->foreign('id_num')->references('id_num')->on('employees')->onDelete('cascade');
            $table->unique(['id_num', 'date_today']); // one log per employee per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};