<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_compositions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2);
            $table->unsignedTinyInteger('age');
            $table->enum('gender', ['male', 'female']);
            $table->enum('activity_level', [
                'no_activity',  // little or no exercise
                'light',        // 1–3x/week
                'moderate',     // 3–5x/week
                'active',       // 6–7x/week
                'very_active',  // twice/day, intense
            ]);
            $table->enum('goal', ['lose_weight', 'maintain', 'gain_muscle']);

            $table->decimal('bmi', 5, 2);
            $table->decimal('bmr', 8, 2);   // Basal Metabolic Rate (kcal/day)
            $table->decimal('tdee', 8, 2);  // Total Daily Energy Expenditure

            $table->unsignedInteger('daily_calories');
            $table->decimal('daily_protein_g', 6, 2);
            $table->decimal('daily_carbs_g', 6, 2);
            $table->decimal('daily_fat_g', 6, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_compositions');
    }
};
