<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('body_compositions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2);
            $table->decimal('body_fat_percent', 5, 2);

            $table->decimal('muscle_mass_kg', 5, 2);
            $table->decimal('bmi', 4, 2);

            $table->decimal('visceral_fat_level', 4, 2)->nullable();
            $table->decimal('water_percent', 5, 2)->nullable();
            $table->decimal('bone_mass_kg', 5, 2)->nullable();

            $table->dateTime('measured_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_compositions');
    }
};
