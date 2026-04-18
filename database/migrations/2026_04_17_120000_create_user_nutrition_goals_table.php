<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_nutrition_goals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('calories');
            $table->decimal('protein_g', 6, 2);
            $table->decimal('carbs_g', 6, 2);
            $table->decimal('fat_g', 6, 2);

            $table->string('goal');           // 'build_muscle' | 'lose_fat' | 'maintain'
            $table->string('activity_level'); // 'sedentary' | 'light' | 'moderate' | 'active' | 'very_active'

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_nutrition_goals');
    }
};
