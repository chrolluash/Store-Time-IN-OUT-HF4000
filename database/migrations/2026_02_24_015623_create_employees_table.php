<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('id_num', 50)->unique();
            $table->string('full_name');
            $table->string('position');
            $table->string('store_dept');
            $table->time('shift_from');
            $table->time('shift_to');
            $table->longText('fingerprint_1')->nullable(); // left index - Base64
            $table->longText('fingerprint_2')->nullable(); // right index - Base64
            $table->timestamps(); // created_at = create_day, updated_at = update_day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};