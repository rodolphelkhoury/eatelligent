<?php

namespace App\Actions;

use App\Enums\ActivityLevel;
use App\Models\BodyComposition;
use App\Models\User;
use App\Models\UserNutritionGoal;
use Illuminate\Support\Facades\Http;

class GenerateNutritionGoal
{
    public function execute(User $user, BodyComposition $composition): UserNutritionGoal
    {
        $prompt = $this->buildPrompt($composition);

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 300,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('AI service unavailable. Please try again later.');
        }

        $text = $response->json('content.0.text');

        return $this->parseAndSave($user, $composition, $text);
    }

    private function buildPrompt(BodyComposition $composition): string
    {
        $activityLabel = ActivityLevel::from($composition->activity_level)->name;

        return <<<PROMPT
You are a clinical nutritionist. Based on the user's body composition and goal, calculate their daily macro targets.

Body Data:
- Weight: {$composition->weight_kg} kg
- Height: {$composition->height_cm} cm
- Body fat: {$composition->body_fat_percent}%
- Muscle mass: {$composition->muscle_mass_kg} kg
- BMI: {$composition->bmi}
- Activity level: {$activityLabel}
- Goal: {$composition->goal}

Respond ONLY with a valid JSON object, no explanation, no markdown, no extra text. Format:
{"calories": 2200, "protein_g": 165, "carbs_g": 220, "fat_g": 73}

Calculate using Mifflin-St Jeor for TDEE, then adjust:
- build_muscle: +10% calories, high protein (2g/kg lean mass)
- lose_fat: -20% calories, high protein (2.2g/kg lean mass), lower carbs
- maintain: TDEE, moderate protein (1.6g/kg lean mass)
PROMPT;
    }

    private function parseAndSave(User $user, BodyComposition $composition, string $aiResponse): UserNutritionGoal
    {
        $data = json_decode(trim($aiResponse), true);

        if (! $data || ! isset($data['calories'], $data['protein_g'], $data['carbs_g'], $data['fat_g'])) {
            throw new \Exception('Failed to parse nutrition goal from AI response.');
        }

        // Upsert: one active goal per user at a time
        return UserNutritionGoal::updateOrCreate(
            ['user_id' => $user->id],
            [
                'calories' => (int) $data['calories'],
                'protein_g' => (float) $data['protein_g'],
                'carbs_g' => (float) $data['carbs_g'],
                'fat_g' => (float) $data['fat_g'],
                'goal' => $composition->goal,
                'activity_level' => $composition->activity_level,
            ]
        );
    }
}
